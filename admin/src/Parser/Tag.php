<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_kunenatopic2article
 *
 * Original code: Chris Konnertz <chriskonnertz@googlemail.com>
 * @copyright   (C) 2012–2023 Chris Konnertz (MIT license)
 * @copyright   (C) 2025 Leonid Ratner. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 * @license     MIT (for original portions); see LICENSE_MIT.txt
 */

namespace Joomla\Component\Kunenatopic2Article\Administrator\Parser;

defined('_JEXEC') or die;

class Tag
{

    /**
     * The name of the tag
     *
     * @var string|null
     */
    public $name = null;

    /**
     * The value of the property
     *
     * @var string
     */
    public $property = null;

    /**
     * Is this an opening tag (true)?
     *
     * @var bool
     */
    public $opening = true;

    /**
     * Is this tag valid?
     *
     * @var bool
     */
    public $valid = true;

    /**
     * Position of this tag inside the whole BBCode string
     *
     * @var int
     */
    public $position = -1;

    /**
     * Tag constructor.
     *
     * @param string|null $name    The name of the tag
     * @param bool        $opening Is this an opening tag (true)?
     */
    public function __construct($name = null, $opening = true)
    {
        if ($name !== null and ! is_string($name)) {
            throw new \InvalidArgumentException('The "name" parameter has to be of type string');
        }
        if (! is_bool($opening)) {
            throw new \InvalidArgumentException('The "opening" parameter has to be of type bool');
        }

        $this->name     = $name;
        $this->opening  = $opening;
    }

}
