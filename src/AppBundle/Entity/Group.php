<?php

namespace AppBundle\Entity;

use FOS\UserBundle\Model\Group as BaseGroup;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;


/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\GroupRepository")
 * @ORM\Table(name="fos_group")
 */
class Group extends BaseGroup
{
    /**
     * @Assert\Callback
     * @param ExecutionContextInterface $context
     * @param $payload
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {

        if (count($this->roles) == 0) {
            $context->buildViolation('group.roles.blank')
                ->atPath('roles')
                ->addViolation();
        }else{
            foreach($this->roles as $role){
                if(!preg_match("/^ROLE_[A-Z0-9_]+$/",$role)){
                    $context->buildViolation('group.roles.format')
                        ->atPath('roles')
                        ->addViolation();
                    break;
                }
            }
        }
        if (empty($this->name)) {
            $context->buildViolation('fos_user.group.blank')
                ->atPath('name')
                ->addViolation();
        }
    }
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank(message="Please enter the description.")
     * @ORM\Column(type="text")
     */
    protected $description;
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getRoleNames()
    {
        return implode(", ",$this->getRoles());
    }


}
