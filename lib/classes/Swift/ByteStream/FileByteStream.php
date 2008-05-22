<?php

/*
 Bi-Directional FileStream in Swift Mailer.
 
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.
 
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 
 */

//@require 'Swift/InputByteStream.php';
//@require 'Swift/FileStream.php';

/**
 * Allows reading and writing of bytes to and from a file.
 * @package Swift
 * @subpackage ByteStream
 * @author Chris Corbyn
 */
class Swift_ByteStream_FileByteStream
  implements Swift_InputByteStream, Swift_FileStream
{
  
  /**
   * The internal pointer offset.
   * @var int
   * @access private
   */
  private $_offset = 0;
  
  /**
   * The path to the file.
   * @var string
   * @access private
   */
  private $_path;
  
  /**
   * The mode this file is opened in for writing.
   * Reads will always use 'rb'.
   * @var string
   * @access private
   */
  private $_mode;
  
  /**
   * A lazy-loaded resource handle for reading the file.
   * @var resource
   * @access private
   */
  private $_reader;
  
  /**
   * A lazy-loaded resource handle for writing the file.
   * @access private
   */
  private $_writer;
  
  /**
   * If magic_quotes_runtime is on, this will be true.
   * @var boolean
   * @access private
   */
  private $_quotes = false;
  
  /**
   * Create a new FileByteStream for $path.
   * @param string $path
   * @param string $writable if true
   */
  public function __construct($path, $writable = false)
  {
    $this->_path = $path;
    $this->_mode = $writable ? 'w+b' : 'rb';
    $this->_quotes = get_magic_quotes_runtime();
  }
  
  /**
   * Get the complete path to the file.
   * @return string
   */
  public function getPath()
  {
    return $this->_path;
  }
  
  /**
   * Reads $length bytes from the stream into a string and moves the pointer
   * through the stream by $length. If less bytes exist than are requested the
   * remaining bytes are given instead. If no bytes are remaining at all, boolean
   * false is returned.
   * @param int $length
   * @return string
   */
  public function read($length)
  {
    $fp = $this->_getReadHandle();
    if (!feof($fp))
    {
      if ($this->_quotes)
      {
        set_magic_quotes_runtime(0);
      }
      $bytes = fread($fp, $length);
      if ($this->_quotes)
      {
        set_magic_quotes_runtime(1);
      }
      $this->_offset = ftell($fp);
      return $bytes;
    }
    else
    {
      return false;
    }
  }
  
  /**
   * Writes $bytes to the end of the stream.
   * @param string $bytes
   * @param Swift_InputByteStream $is, optional
   */
  public function write($bytes, Swift_InputByteStream $is = null)
  {
    $fp = $this->_getWriteHandle();
    fwrite($fp, $bytes);
    $this->_resetReadHandle();
    if (isset($is))
    {
      $is->write($bytes);
    }
  }
  
  /**
   * Move the internal read pointer to $byteOffset in the stream.
   * @param int $byteOffset
   * @return boolean
   */
  public function setReadPointer($byteOffset)
  {
    if (isset($this->_reader))
    {
      fseek($this->_reader, $byteOffset, SEEK_SET);
    }
    $this->_offset = $byteOffset;
  }
  
  /**
   * Flush the contents of the stream (empty it) and set the internal pointer
   * to the beginning.
   */
  public function flushContents()
  {
    file_put_contents($this->_path, '');
    $this->_resetWriteHandle();
    $this->_resetReadHandle();
    $this->_offset = 0;
  }
  
  // -- Private methods
  
  /**
   * Get the resource for reading.
   * @return resource
   * @access private
   */
  private function _getReadHandle()
  {
    if (!isset($this->_reader))
    {
      if (!$this->_reader = fopen($this->_path, 'rb'))
      {
        throw new Exception(
          'Unable to open file for reading [' . $this->_path . ']'
          );
      }
      fseek($this->_reader, $this->_offset, SEEK_SET);
    }
    return $this->_reader;
  }
  
  /**
   * Get the resource for writing.
   * @return resource
   * @access private
   */
  private function _getWriteHandle()
  {
    if (!isset($this->_writer))
    {
      if (!$this->_writer = fopen($this->_path, $this->_mode))
      {
        throw new Exception(
          'Unable to open file for writing [' . $this->_path . ']'
          );
      }
    }
    return $this->_writer;
  }
  
  /**
   * Force a reload of the resource for writing.
   * @return resource
   * @access private
   */
  private function _resetWriteHandle()
  {
    if (isset($this->_writer))
    {
      fclose($this->_writer);
      $this->_writer = null;
    }
  }
  
  /**
   * Force a reload of the resource for reading.
   * @return resource
   * @access private
   */
  private function _resetReadHandle()
  {
    if (isset($this->_reader))
    {
      fclose($this->_reader);
      $this->_reader = null;
    }
  }
  
}
