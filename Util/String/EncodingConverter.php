<?php

namespace Contrib\CommonBundle\Util\String;

/**
 * String encoding converter.
 */
abstract class EncodingConverter
{
    /**
     * from_encoding of mb_convert_encoding.
     *
     * @var string
     */
    const AUTO = 'auto';
}
