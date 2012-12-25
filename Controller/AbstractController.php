<?php

namespace Contrib\CommonBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Abstract controller.
 *
 * This controller offers some useful shortcut methods.
 */
abstract class AbstractController extends Controller
{
    /**
     * Flash identifier in a controller.
     *
     * @var string
     */
    protected $flashKey = null;

    /**
     * Return Session object.
     *
     * @return Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    public function getSession()
    {
        return $this->getRequest()->getSession();
    }

    // flash message

    /**
     * Set success message to flash.
     *
     * @param string $message
     * @return void
     */
    public function setSuccessMessage($message)
    {
        $this->getSession()->setFlash('success', $message);
    }

    /**
     * Set info message to flash.
     *
     * @param string $message
     * @return void
     */
    public function setInfoMessage($message)
    {
        $this->getSession()->setFlash('info', $message);
    }

    /**
     * Set notice message to flash.
     *
     * @param string $message
     * @return void
     */
    public function setNoticeMessage($message)
    {
        $this->getSession()->setFlash('notice', $message);
    }

    /**
     * Set error message to flash.
     *
     * @param string $message
     * @return void
     */
    public function setErrorMessage($message)
    {
        $this->getSession()->setFlash('error', $message);
    }

    // flash data

    /**
     * Set flash data.
     *
     * @param mixed $data
     * @return void
     */
    public function setFlash($data)
    {
        if (null === $data) {
            $serialized = null;
        } else {
            $serialized = serialize($data);
        }

        $this->getSession()->setFlash('my_data', $serialized);
    }

    /**
     * Delete all flash data.
     *
     * @return void
     */
    public function deleteFlash()
    {
        $this->setFlash(null);
    }

    /**
     * Return all flash data.
     *
     * @return NULL|mixed
     */
    public function getFlash()
    {
        $serialized = $this->getSession()->getFlash('my_data');

        if (null === $serialized) {
            return null;
        }

        return unserialize($serialized);
    }

    /**
     * Set controller-specific flash data.
     *
     * @param mixed $data
     * @return void
     */
    public function setMyFlash($data)
    {
        if (null === $data) {
            $myData = null;
        } else {
            $myData = array($this->flashKey => $data);
        }

        $this->setFlash($myData);
    }

    /**
     * Delete controller-specific flash data.
     *
     * Other flash data remains.
     *
     * @return void
     */
    public function deleteMyFlash()
    {
        $flash = $this->getFlash();
        unset($flash[$this->flashKey]);

        $this->setFlash($flash);
    }

    /**
     * Return controller-specific flash data.
     *
     * @return NULL|mixed
     */
    public function getMyFlash()
    {
        $myData = $this->getFlash();

        if (null === $myData || !isset($myData[$this->flashKey])) {
            return null;
        }

        return $myData[$this->flashKey];
    }

    /**
     * Return my flash if the flash data exists, 404 otherwise.
     *
     * @param string $name
     * @return mixed
     */
    public function assertMyFlash($name)
    {
        $flash = $this->getMyFlash();

        if (!array_key_exists($name, $flash)) {
            throw $this->createNotFoundException('Unable to find flash.');
        }

        $this->setMyFlash($flash);

        return $flash;
    }

    // request method utility

    /**
     * Return whether the request method is HEAD.
     *
     * @return boolean
     */
    public function isHead()
    {
        return $this->getRequest()->isMethod('HEAD');
    }

    /**
     * Return whether the request method is GET.
     *
     * @return boolean
     */
    public function isGet()
    {
        return $this->getRequest()->isMethod('GET');
    }

    /**
     * Return whether the request method is POST.
     *
     * @return boolean
     */
    public function isPost()
    {
        return $this->getRequest()->isMethod('POST');
    }

    /**
     * Return whether the request method is PUT.
     *
     * @return boolean
     */
    public function isPut()
    {
        return $this->getRequest()->isMethod('PUT');
    }

    /**
     * Return whether the request method is DELETE.
     *
     * @return boolean
     */
    public function isDelete()
    {
        return $this->getRequest()->isMethod('DELETE');
    }

    // redirection

    /**
     * Shortcut for $this->redirect($this->generateUrl($name, $param, $absolute));
     *
     * @param string $name
     * @param array  $param
     * @param string $absolute
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToInternal($name, $param = array(), $absolute = false)
    {
        if (is_array($param)) {
            //public function redirect($url, $status = 302)
            //public function generateUrl($route, $parameters = array(), $absolute = false)
            return $this->redirect($this->generateUrl($name, $param, $absolute));
        }

        return $this->redirect($this->generateUrl($name, array(), $absolute));
    }

    // entity manager

    /**
     * Return entity manager.
     *
     * @param string $name The object manager name (null for the default one)
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getManager($name = null)
    {
        return $this->getDoctrine()->getManager($name);
    }

    /**
     * Flushes all changes to objects that have been queued up to now to the database.
     * This effectively synchronizes the in-memory state of managed objects with the database.
     */
    public function flush($name = null)
    {
        return $this->getManager($name)->flush();
    }
}
