<?php

namespace App\Form;

use App\Entity\Character;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $edit = false;
        if (isset($options['edit'])) {
            $edit = $options['edit'];
        }
        $builder
            ->add('name')
            ->add('mass')
            ->add('height')
            ->add('gender');
        if ($edit != true) {
            $builder
                ->add('picture', FileType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
            'edit' => null
        ]);
    }
}
