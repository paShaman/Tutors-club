<?php

if (! function_exists('lng')) {
    /**
     * Translate the given message.
     *
     * @param  string|null  $id
     * @param  array   $replace
     * @param  string|null  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    function lng($id = null, $replace = [], $locale = null)
    {
        return trans('messages.' . $id, $replace, $locale);
    }
}