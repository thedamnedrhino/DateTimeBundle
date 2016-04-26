<?php
namespace Mohebifar\DateTimeBundle\Form;

use Mohebifar\DateTimeBundle\Calendar\Proxy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mohebifar\DateTimeBundle\Form\Transformer\DateTimeToArrayTransformer;

/**
 * Class DateTimeType
 * @package Mohebifar\DateTimeBundle\Form
 * @author Mohammad Mohebifar <mohamad@mohebifar.com>
 * @author Masoud Zohrabi <mdzzohrabi@gmail.com>
 */
class DateTimeType extends AbstractType
{

    /**
     * @var \Mohebifar\DateTimeBundle\Calendar\Proxy
     */
    private $date;

    public function __construct(Proxy $date)
    {
        $this->date = $date;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $yearChoices = $options['years'];
        $monthChoices = $this->formatMonths($options['months']);
        $dayChoices = $options['days'];

        if ( !$options['required'] ) {
            $yearChoices   = [ $options['empty_data'] ] + $yearChoices;
            $monthChoices = [ $options['empty_data'] ] + $monthChoices;
            $dayChoices = [ $options['empty_data'] ] + $dayChoices;
        }

        // Symfony 3.0 Changes
        if ( method_exists( AbstractType::class , 'getBlockPrefix' ) ) {
            $choiceType = ChoiceType::class;
            $textType = TextType::class;
        } else {
            $choiceType = 'choice';
            $textType = 'text';
        }

        $labeled = $options['with_label'];

        if($options['widget'] == 'choice') {
            $builder
                ->add('year', $choiceType, array(
                    'choices' => $yearChoices,
                    'choice_translation_domain' => false
                ))
                ->add('month', $choiceType, array(
                    'choices' => $monthChoices,
                    'choice_translation_domain' => false
                ))
                ->add('day', $choiceType, array(
                    'choices' => $dayChoices,
                    'choice_translation_domain' => false
                ))
                ->addModelTransformer(new DateTimeToArrayTransformer(
                    $this->date, $options['model_timezone'], $options['view_timezone'], [ 'year' , 'month' , 'day' ]
                ));
        } elseif($options['widget'] == 'jquery') {
            $builder
                ->add('date', $textType)
               ;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['widget'] = $options['widget'];
        $pattern = '{{ year }}{{ month }}{{ day }}';
        $view->vars['date_pattern'] = $pattern;
        $view->vars['driver'] = strtolower($this->date->getDriver());


    }

    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver
            ->setDefaults(array(
                'years' => array_combine(
                    $years = range($this->date->format('Y') - 5, $this->date->format('Y') + 5),
                    $years
                ),
                'months' => array_combine( $months = range(1, 12) , $months ),
                'days' => array_combine( $days  = range(1, 31) , $days ),
                'widget' => 'choice',
                'input' => 'datetime',
                'model_timezone' => null,
                'view_timezone' => null,
                'by_reference' => false,
                'error_bubbling' => false,
                'data_class' => null,
                'required'      => true,
                'empty_data'    => '-',
                'with_label'    => true
            ))
            ->setAllowedValues( 'input' , array( 'datetime' , 'timestamp' , 'array' ) )
            ->setAllowedValues( 'widget' , array( 'text' , 'choice' , 'jquery' ) )
        ;

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mohebifar_datetime';
    }

    private function formatMonths($months, $format = 'F')
    {
        $formatted = array();
        foreach ($months as $i => $month) {
            $timestamp = $this->date->makeTime(0, 0, 0, $month, 1, null);
            $formatted[$i] = $this->date->format($format, $timestamp);
        }

        return $formatted;
    }
}
