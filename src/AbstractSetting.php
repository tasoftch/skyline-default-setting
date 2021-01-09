<?php
/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2019, TASoft Applications
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

namespace Skyline\Setup;

use TASoft\Service\ServiceManager;
use TASoft\Util\PDO;

abstract class AbstractSetting
{
	const RECORD_ID_KEY = 'id';
	const RECORD_NAME_KEY = 'name';
	const RECORD_CONTENT_KEY = 'content';
	const RECORD_MULTIPLE_KEY = 'multiple';

	const PDO_SERVICE_KEY = 'PDO';

	/** @var array */
	private $settings;
	protected static $setting;

	abstract protected function getTableName(): string;

	protected function __construct() {
		/** @var PDO $PDO */
		$PDO = ServiceManager::generalServiceManager()->get(static::PDO_SERVICE_KEY);

		$table = $this->getTableName();
		$n = static::RECORD_NAME_KEY;
		$c = static::RECORD_CONTENT_KEY;
		$m = static::RECORD_MULTIPLE_KEY;

		foreach($PDO->select("SELECT DISTINCT $n, $c, $m FROM $table") as $record) {
			if($k = $record[ $n ] ?? NULL) {
				$m = $record[ $m ] ?? 0;
				$cnt = $record[ $c ] ?? NULL;

				if($m)
					$this->settings[$k][] = $cnt;
				else
					$this->settings[$k] = $cnt;
			}
		}
	}

	/**
	 * Creates the desired setting instance once or return it if it was already created.
	 *
	 * @return static
	 */
	public static function getDefaultSetting() {
		if(!static::$setting)
			static::$setting = new static();
		return static::$setting;
	}

	/**
	 * Fetches a setting
	 *
	 * @param $key
	 * @param mixed|null $default
	 * @return mixed|null
	 */
	public function getSetting($key, $default = NULL) {
		return $this->settings[$key] ?? $default;
	}

	/**
	 * @param string $key
	 * @param $value
	 * @param bool $temporary
	 * @param bool $multiple
	 */
	public function setSetting(string $key, $value, bool $temporary = false, bool $multiple = false) {
		if($multiple) {
			if(NULL === $this->settings[$key] || is_array($this->settings[$key]))
				$this->settings[$key][] = $value;
		} else
			$this->settings[$key] = $value;

		if(!$temporary) {
			/** @var PDO $PDO */
			$PDO = ServiceManager::generalServiceManager()->get("PDO");

			$table = $this->getTableName();
			$i = static::RECORD_ID_KEY;
			$n = static::RECORD_NAME_KEY;
			$c = static::RECORD_CONTENT_KEY;
			$m = static::RECORD_MULTIPLE_KEY;

			if($multiple)
				$PDO->inject("INSERT INTO $table ($n, $c, $m) VALUES (?, ?, 1)")->send([
					$key,
					$value
				]);
			else {
				$vid = $PDO->selectFieldValue("SELECT $i FROM $table WHERE $n = ?", $i, [$key]);
				if($vid)
					$PDO->inject("UPDATE $table SET $c = ? WHERE $i = $vid")->send([$value]);
				else
					$PDO->inject("INSERT INTO $table ($n, $c, $m) VALUES (?, ?, 0)")->send([
						$key,
						$value
					]);
			}
		}
	}

	/**
	 * @param string $key
	 * @param bool $temporary
	 */
	public function removeSetting(string $key, bool $temporary = false) {
		if(isset($this->settings[$key]))
			unset($this->settings[$key]);

		if(!$temporary) {
			/** @var PDO $PDO */
			$PDO = ServiceManager::generalServiceManager()->get("PDO");
			$table = $this->getTableName();
			$n = static::RECORD_NAME_KEY;
			$PDO->inject("DELETE FROM $table WHERE $n = ?")->send([$key]);
		}
	}

	/**
	 * @return array
	 */
	public function getSettings(): array
	{
		return $this->settings;
	}
}