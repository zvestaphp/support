<?php

declare(strict_types=1);

namespace Rinvex\Support\Traits;

use Illuminate\Support\Str;
use Spatie\Translatable\Events\TranslationHasBeenSet;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslation($key, config('app.locale')) ?: array_first($this->getTranslations($key));
    }

    /**
     * Get translations.
     *
     * @param $key
     *
     * @return array
     */
    public function getTranslations($key): array
    {
        $this->guardAgainstUntranslatableAttribute($key);
        $value = json_decode($this->getAttributes()[$key] ?? '' ?: '{}', true);

        // Inject default translation if none supplied
        if (! is_array($value)) {
            $oldValue = $value;

            if ($this->hasSetMutator($key)) {
                $method = 'set'.Str::studly($key).'Attribute';
                $value = $this->{$method}($value);
            }

            $value = [$locale = app()->getLocale() => $value];

            $this->attributes[$key] = $this->asJson($value);
            event(new TranslationHasBeenSet($this, $key, $locale, $oldValue, $value));
        }

        return $value;
    }
}
