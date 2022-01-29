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

use Skyline\Setup\Exception\ReadonlySettingException;
use TASoft\Service\ServiceManager;
use TASoft\Util\PDO;

/**
 * Class AbstractReadonlyTableSetting
 *
 * Adds readonly settings to the instance.
 * Please note that duplicated settings are handled as following:
 *
 * Readonly settings are loaded before the others. So settings with the same name are overwritten.
 * Readonly setting names prevent changing on it!
 *
 * @package Skyline\Setup
 */
abstract class AbstractReadonlyTableSetting extends AbstractSetting
{
	/**
	 * Specifies a readonly table. Values are fetched but can not be changed anymore.
	 *
	 * @return string
	 */
	abstract protected function getReadonlyTableName(): string;

	private $load_ro=true;
	private $readOnlySettingNames = [];

	public function __construct()
	{
		/** @var PDO $PDO */
		$PDO = ServiceManager::generalServiceManager()->get(static::PDO_SERVICE_KEY);
		$table = $this->getReadonlyTableName();

		$this->importSettingsFromTable($table, $PDO);
		$this->load_ro=false;

		parent::__construct();
	}

	/**
	 * @inheritDoc
	 */
	protected function isMultipleRecord($record): bool
	{
		if($this->load_ro)
			$this->readOnlySettingNames[] = $record[static::RECORD_NAME_KEY];

		return parent::isMultipleRecord($record);
	}

	/**
	 * @inheritDoc
	 */
	public function setSetting(string $key, $value, bool $temporary = false, bool $multiple = false)
	{
		if(in_array($key, $this->readOnlySettingNames))
			throw (new ReadonlySettingException("Can not change readonly setting", 401))->setSettingName($key);
		parent::setSetting($key, $value, $temporary, $multiple);
	}

	/**
	 * @inheritDoc
	 */
	public function removeSetting(string $key, bool $temporary = false)
	{
		if(in_array($key, $this->readOnlySettingNames))
			throw (new ReadonlySettingException("Can not remove readonly setting", 402))->setSettingName($key);
		parent::removeSetting($key, $temporary);
	}
}