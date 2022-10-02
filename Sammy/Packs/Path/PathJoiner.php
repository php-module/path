<?php
/**
 * @version 2.0
 * @author Sammy
 *
 * @keywords Samils, ils, php framework
 * -----------------
 * @package Sammy\Packs\Path
 * - Autoload, application dependencies
 *
 * MIT License
 *
 * Copyright (c) 2020 Ysare
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Sammy\Packs\Path {
  use php\module;
  /**
   * Make sure the module base internal trait is not
   * declared in the php global scope defore creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!trait_exists ('Sammy\Packs\Path\PathJoiner')) {
  /**
   * @trait PathJoiner
   * Base internal trait for the
   * Path module.
   * -
   * This is (in the ils environment)
   * an instance of the php module,
   * wich should contain the module
   * core functionalities that should
   * be extended.
   * -
   * For extending the module, just create
   * an 'exts' directory in the module directory
   * and boot it by using the ils directory boot.
   * -
   */
  trait PathJoiner {
    /**
     * @method string join
     */
    public function join () {
      $backTrace = debug_backtrace ();

      $pathSlices = func_get_args ();

      if (count ($pathSlices) >= 1) {
        $lastFuncArg = $pathSlices [-1 + count ($pathSlices)];

        if (self::validTrace ($lastFuncArg)) {
          $backTrace = $lastFuncArg;
        }
      }

      return $this->joinArray ($pathSlices, $backTrace);
    }

    /**
     * @method string joinArray
     */
    public function joinArray (array $pathSlices, array $backTrace = null) {
      if (!self::validTrace ($backTrace)) {
        $backTrace = debug_backtrace ();
      }

      $pathSlices = array_filter ($pathSlices, function ($slice) {
        return is_string ($slice) && !empty ($slice);
      });

      if (count ($pathSlices) <= 0) {
        return;
      }

      $slashRe = '/(\/|\\\\)/';
      $currentDirRefRe = '/^\.(\/|\\\\)/';

      $path = join (DIRECTORY_SEPARATOR, $pathSlices);

      $path = preg_replace ($slashRe, DIRECTORY_SEPARATOR, $path);

      if (preg_match ($currentDirRefRe, $path)) {
        $backTraceFileDir = dirname ($backTrace [0]['file']);

        $absolutePath = join (DIRECTORY_SEPARATOR, [
          $backTraceFileDir, preg_replace ($currentDirRefRe, '', $path)
        ]);

        return file_exists ($absolutePath) ? realpath ($absolutePath) : $absolutePath;
      }

      if (class_exists (module::class)) {
        $pathSlices = preg_split ($slashRe, $path);
        $pathAlias = $pathSlices [0];

        if (module::definedPath ($pathAlias)) {
          $absolutePath = join (DIRECTORY_SEPARATOR, array_merge (
            [module::readPath ($pathAlias, $backTrace)],
            array_slice ($pathSlices, 1, count ($pathSlices))
          ));

          return file_exists ($absolutePath) ? realpath ($absolutePath) : $absolutePath;
        }
      }

      return $path;
    }
  }}
}
