<?php
/**
 * bytekit-cli
 *
 * Copyright (c) 2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package   Bytekit
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @since     File available since Release 1.0.0
 */

/**
 * Scans a set of files using a set of rules.
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright 2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version   Release: @package_version@
 * @link      http://github.com/sebastianbergmann/bytekit-cli/tree
 * @since     Class available since Release 1.0.0
 */
class Bytekit_Scanner
{
    /**
     * @var array
     */
    protected $singlePassRules = array();

    /**
     * @var array
     */
    protected $twoPassRules = array();

    /**
     * Constructor.
     *
     * @param array $rules
     */
    public function __construct(array $rules)
    {
        foreach ($rules as $rule) {
            if ($rule instanceof Bytekit_Scanner_Rule_TwoPass) {
                $this->twoPassRules[] = $rule;
            }

            else if ($rule instanceof Bytekit_Scanner_Rule) {
                $this->singlePassRules[] = $rule;
            }
        }

        $this->rules = $rules;
    }

    /**
     * Scans a set of files using a set of rules.
     *
     * @param  array $files
     * @return array
     */
    public function scan(array $files)
    {
        $result = array();

        $this->doScan(
          $files,
          array_merge($this->singlePassRules, $this->twoPassRules),
          $result,
          FALSE
        );

        if (!empty($this->twoPassRules)) {
            $this->doScan($files, $this->twoPassRules, $result, TRUE);
        }

        return $result;
    }

    /**
     * @param array   $files
     * @param array   $rules
     * @param array   $result
     * @param boolean $secondPass
     */
    protected function doScan(array $files, array $rules, array &$result, $secondPass)
    {
        foreach ($files as $file) {
            $bytecode = @bytekit_disassemble_file($file);

            if (!$bytecode) {
                printf(
                  'WARNING: Could not disassemble "%s". ' .
                  "Check for syntax errors.\n",
                  $file
                );

                continue;
            }

            foreach ($bytecode['functions'] as $function => $oparray) {
                foreach ($rules as $rule) {
                    if (!$secondPass) {
                        $rule->process($oparray, $file, $function, $result);
                    } else {
                        $rule->secondPass($oparray, $file, $function, $result);
                    }
                }
            }
        }
    }
}
?>
