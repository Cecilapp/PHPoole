<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

/**
 * Class Result.
 */
Class Result
{
    /**
     * @var bool
     */
    protected $success;
    /**
     * @var string
     */
    protected $trace;

    /**
     * Result constructor.
     *
     * @param $success
     * @param $trace
     */
    public function __construct($success, $trace)
    {
        $this->success = $success;
        $this->trace = $trace;
    }

    /**
     * Return true in case of build success.
     *
     * @return bool
     */
    public function isSuccess()
    {
        if ($this->success) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getPhrase()
    {
        if ($this->isSuccess()) {
            return 'OK';
        }

        return 'KO';
    }

    /**
     * @return string
     */
    public function getTrace()
    {
        return $this->trace;
    }
}
