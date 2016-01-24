<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Renderer;

/**
 * Interface RendererInterface.
 */
interface RendererInterface
{
    /**
     * Constructor.
     *
     * @param string|array $templatesPath
     */
    public function __construct($templatesPath = []);

    /**
     * Add templates path.
     *
     * @param $path
     *
     * @return void
     */
    public function addPath($path);

    /**
     * Add global variable.
     *
     * @param $name
     * @param $value
     *
     * @return void
     */
    public function addGlobal($name, $value);

    /**
     * Rendering.
     *
     * @param $template
     * @param $variables
     *
     * @return self
     */
    public function render($template, $variables);

    /**
     * Saves rendered template.
     *
     * @param $pathname
     *
     * @return bool
     */
    public function save($pathname);

    /**
     * Validates template.
     *
     * @param $template
     *
     * @return bool
     */
    public function validate($template);
}
