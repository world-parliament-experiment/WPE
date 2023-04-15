<?php
/**
 * Created by PhpStorm.
 * User: Konstantin Borchert
 * Date: 03.06.2019
 * Time: 21:49
 */

namespace AppBundle\Controller;


use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\Persistence\ManagerRegistry;

class BaseController extends AbstractController
{

    public $_serializeGroups = array();
    public $serializer;
    protected $managerRegistry;
    /**
     * BaseController constructor.
     */
    public function __construct(SerializerInterface $serializer, ManagerRegistry $managerRegistry)
    {
        $this->_serializeGroups = ["default"];
        $this->serializer = $serializer;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * creates 403 error response
     *
     * @return JsonResponse
     */
    protected function respondForbiddenError()
    {
        return $this->createApiResponse('Forbidden!', 403);
    }

    /**
     * creates 404 error response
     *
     * @return JsonResponse
     */
    protected function respondNotFoundError()
    {
        return $this->createApiResponse('Not found!', 404);
    }

    /**
     * creates 422 unprocessable entity
     *
     * @return JsonResponse
     */
    protected function respondValidationError()
    {
        return $this->createApiResponse('Validation Error!', 422);
    }

    /**
     * @param mixed $data Usually an object you want to serialize
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function createApiResponse($data, $statusCode = 200)
    {
        $json = $this->serialize($data);

        return new JsonResponse($json, $statusCode, [], true);
    }

    protected function serialize($data, $format = 'json')
    {
        $context = new SerializationContext();
        $context->setSerializeNull(true);

//        $request = $this->get('request_stack')->getCurrentRequest();
//        $groups = array('simple');
//        if ($request->query->get('deep')) {
//            $groups[] = 'deep';
//        }
        $context->setGroups($this->_serializeGroups);

        return $this->serializer->serialize($data, $format, $context);
    }

    /**
     * Returns an associative array of validation errors
     *
     * {
     *     'firstName': 'This value is required',
     *     'subForm': {
     *         'someField': 'Invalid value'
     *     }
     * }
     *
     * @param FormInterface $form
     * @return array|string
     */

    protected function getErrorsFromForm(FormInterface $form)
    {
        dd("safasfasfasfasffas");
        foreach ($form->getErrors() as $error) {
            // only supporting 1 error per field
            // and not supporting a "field" with errors, that has more
            // fields with errors below it
            return $error->getMessage();
        }

        $errors = array();
        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childError = $this->getErrorsFromForm($childForm)) {
                    $errors[$childForm->getName()] = $childError;
                }
            }
        }

        return $errors;
    }

    protected function setSerializeGroups($groups) {
        if (!is_array($groups)) return false;
        $this->_serializeGroups = $groups;
        return $this->_serializeGroups;
    }
}