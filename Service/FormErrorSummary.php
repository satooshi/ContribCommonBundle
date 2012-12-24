<?php

namespace Contrib\CommonBundle\Service;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Form error summary.
 *
 * configure in service.yml:
 *
 * parameters:
 *     my.form_error_summary.class: Contrib\CommonBundle\Service\FormErrorSummary
 * services:
 *     my.form_error_summary:
 *         class: %my.form_error_summary.class%
 *         arguments: [@translator]
 *
 * in your controller class:
 *
 * $errors = $this->get('my.form_error_summary')->collectErrors($form);
 *
 * result in:
 * [
 *     'field1' => [
 *         'label'    => 'field label',
 *         'messages' => ['message1', 'message2'],
 *     ],
 *     'field2' => [...],
 * ]
 *
 */
class FormErrorSummary
{
    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Collect validation error messages.
     *
     * @param Form $form
     * @return array
     */
    public function collectErrors(Form $form)
    {
        if (count($form) === 0) {
            // assuming this form is the leaf node
            return $this->collectLeafFormErrors($form);
        }

        // assuming this form is a parent
        return $this->collectParentFormErrors($form);
    }

    // internal method

    /**
     * Collect validation error messages from leaf node form.
     *
     * @param Form $form
     * @return array
     */
    private function collectLeafFormErrors(Form $form)
    {
        $errors = array();

        if ($form->hasErrors()) {
            $errors['label']    = $form->getConfig()->getOption('label');
            $errors['messages'] = $this->collectFormErrors($form);
        }

        return $errors;
    }

    /**
     * Collect validation error message from parent form.
     *
     * @param Form $form
     * @return array
     */
    private function collectParentFormErrors(Form $form)
    {
        $errors = array();
        $formName = $form->getName();

        if ($form->hasErrors()) {
            // form-wide error
            $errors[$formName] = $this->collectFormErrors($form);
        }

        foreach ($form->all() as $child) {
            // field error
            $childErrors = $this->collectErrors($child);

            if (!empty($childErrors)) {
                // merge child errors
                if (!isset($childErrors['messages'])) {
                    foreach ($childErrors as $name => $childError) {
                        $errorName = $formName . '_' . $name;
                        $errors[$errorName] = $childError;
                    }
                } else {
                    $childName = $formName . '_' . $child->getName();
                    $errors[$childName] = $childErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Collect validation error messages.
     *
     * @param Form $form
     * @return array
     */
    private function collectFormErrors(Form $form)
    {
        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $this->translateValidatorMessage($error);
        }

        return $errors;
    }

    /**
     * Translate validation error message.
     *
     * @param FormError $error
     * @return string
     */
    private function translateValidatorMessage(FormError $error)
    {
        return $this->translator->trans(
            $error->getMessageTemplate(),
            $error->getMessageParameters(),
            'validators'
        );
    }
}
