<?php

namespace Contrib\Bundle\CommonBundle\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * mb_trim filter event handler on preBind.
 *
 * in buildForm() method, add
 *
 * if ($options['mbtrim']) {
 *     // include all field
 *     $builder->addEventSubscriber(new MbtrimListener(array_keys($builder->all())));
 *
 *     // include some of fields
 *     $builder->addEventSubscriber(new MbtrimListener(array('field1', 'field2')));
 * }
 *
 * in setDefaultOptions() method, add "mbtrim" option
 *
 * $resolver->setDefaults(array(
 *     'mbtrim' => true,
 * ));
 */
class MbtrimListener implements EventSubscriberInterface
{
    /**
     * Field names to be mbtrimed.
     * @var array
     */
    protected $fieldNames;

    /**
     * Constructor.
     *
     * @param array $fieldNames Field names to be mbtrimed.
     */
    public function __construct(array $fieldNames)
    {
        $this->fieldNames = $fieldNames;
    }

    /**
     * Event handler.
     *
     * @param FormEvent $event
     * @return void
     */
    public function preBind(FormEvent $event)
    {
        // FormEvents::PRE_BIND
        $submittedData = $event->getData();

        if (is_array($submittedData)) {
            // for form data
            foreach ($this->fieldNames as $fieldName) {
                if (isset($submittedData[$fieldName]) && is_string($submittedData[$fieldName])) {
                    $submittedData[$fieldName] = $this->mbtrim($submittedData[$fieldName]);
                }
            }

            $event->setData($submittedData);
        } else if (is_string($submittedData)) {
            // for form field type
            $event->setData($this->mbtrim($submittedData));
        }
    }

    // EventSubscriberInterface

    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_BIND => 'preBind');
    }

    // internal method

    /**
     * mbtrim.
     *
     * @param string $value Submitted string data.
     * @return string mbtrimed string.
     */
    protected function mbtrim($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        if (function_exists('mb_convert_kana')) {
            return trim(mb_convert_kana($value, 's'));
        }

        return trim($value);
    }
}
