<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

use App\Entity\User;

class UserType extends AbstractType
{
    /**
     * Build form.
     * @param  FormBuilderInterface $builder Form builder
     * @param  array                $options Form options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * @var boolean Is the user registered?
         */
        $isRegistered = ($builder->getData() instanceof User && !empty($builder->getData()->getId()));

        $builder
            ->add("username", TextType::class, [
                "required"          => true,
                "label"             => "word.username",
                "disabled"          => $isRegistered,
            ])
            ->add("firstName", TextType::class, [
                "required"          => true,
                "label"             => "phrase.first_name",
            ])
            ->add("lastName", TextType::class, [
                "required"          => true,
                "label"             => "phrase.last_name",
            ])
            ->add("displayName", TextType::class, [
                "required"          => true,
                "label"             => "phrase.display_name",
            ])
            ->add("email", EmailType::class, [
                "required"          => true,
                "label"             => "phrase.email_address",
            ])
            ->add("password", RepeatedType::class, [
                "required"          => !$isRegistered,
                "label"             => !$isRegistered ? "word.password" : "phrase.change_password",
                "constraints"       => [
                    new Assert\NotBlank(),
                    new Assert\NotCompromisedPassword(),
                ],
                "attr"              => [
                    "autocomplete"      => "off",
                ],
                "type"              => PasswordType::class,
                "first_options"     => [
                    "label"             => !$isRegistered ? "word.password" : "phrase.new_password",
                    "attr"              => [
                        "autocomplete"      => "new-password",
                    ],
                ],
                "second_options"    => [
                    "label"             => !$isRegistered ? "phrase.confirm_password" : "phrase.confirm_new_password",
                    "attr"              => [
                        "autocomplete"      => "new-password",
                    ],
                ],
            ])
            ->add("submit", SubmitType::class, [
                "label"             => !$isRegistered ? "word.register" : "word.save",
                "attr"              => [
                    "class"             => "btn btn-primary",
                ],
            ])
        ;
    }

    /**
     * Configure form.
     * @param  OptionsResolver $resolver Options resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            "data_class"    => User::class,
        ]);
    }
}
