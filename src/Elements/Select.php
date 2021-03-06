<?php

namespace Spatie\Html\Elements;

use Illuminate\Support\Str;
use Spatie\Html\Selectable;
use Spatie\Html\BaseElement;
use Illuminate\Support\Collection;

class Select extends BaseElement
{
    /** @var string */
    protected $tag = 'select';

    /** @var array */
    protected $options = [];

    /** @var string|iterable */
    protected $value = '';

    /**
     * @return static
     */
    public function multiple()
    {
        $element = clone $this;

        $element = $element->attribute('multiple');

        $name = $element->getAttribute('name');

        if ($name && ! Str::endsWith($name, '[]')) {
            $element = $element->name($name.'[]');
        }

        $element->applyValueToOptions();

        return $element;
    }

    /**
     * @param string|null $name
     *
     * @return static
     */
    public function name($name)
    {
        return $this->attribute('name', $name);
    }

    /**
     * @param iterable $options
     *
     * @return static
     */
    public function options($options)
    {
        return $this->addChildren($options, function ($text, $value) {
            if (is_array($text)) {
                return $this->optgroup($value, $text);
            }

            return Option::create()
                ->value($value)
                ->text($text)
                ->selectedIf($value === $this->value);
        });
    }

    /**
     * @param string $label
     * @param iterable $options
     *
     * @return static
     */
    public function optgroup($label, $options)
    {
        return Optgroup::create()
            ->label($label)
            ->addChildren($options, function ($text, $value) {
                return Option::create()
                    ->value($value)
                    ->text($text)
                    ->selectedIf($value === $this->value);
            });

        return $this->addChild($optgroup);
    }

    /**
     * @param string|null $text
     *
     * @return static
     */
    public function placeholder($text)
    {
        return $this->prependChild(
            Option::create()
                ->value('')
                ->text($text)
                ->selectedIf(! $this->hasSelection())
        );
    }

    /**
     * @return static
     */
    public function required()
    {
        return $this->attribute('required');
    }

    /**
     * @param string|iterable $value
     *
     * @return static
     */
    public function value($value = null)
    {
        $element = clone $this;

        $element->value = $value;

        $element->applyValueToOptions();

        return $element;
    }

    protected function hasSelection()
    {
        return $this->children->contains->hasAttribute('selected');
    }

    protected function applyValueToOptions()
    {
        $value = Collection::make($this->value);

        if (! $this->hasAttribute('multiple')) {
            $value = $value->take(1);
        }

        $this->children = $this->applyValueToElements($value, $this->children);
    }

    protected function applyValueToElements($value, Collection $children)
    {
        return $children->map(function ($child) use ($value) {
            if ($child instanceof Optgroup) {
                return $child->setChildren($this->applyValueToElements($value, $child->children));
            }

            if ($child instanceof Selectable) {
                return $child->selectedIf($value->containsStrict($child->getAttribute('value')));
            }

            return $child;
        });
    }
}
