<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CEventTagsParserTest extends PHPUnit_Framework_TestCase {

	public function testProvider() {
		return [
			[
				CEventTagsParser::TRIM_QUOTE_MARKS, '{EVENT.TAGS."Jira Id"}', 0, [
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{EVENT.TAGS."Jira Id"}',
					'parts' => ['EVENT', 'TAGS', 'Jira Id']
				]
			],
			[
				CEventTagsParser::TRIM_QUOTE_MARKS, '{EVENT.TAGS."йцукенгшщзфывапролдячсмить"}', 0, [
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{EVENT.TAGS."йцукенгшщзфывапролдячсмить"}',
					'parts' => ['EVENT', 'TAGS', 'йцукенгшщзфывапролдячсмить']
				]
			],
			[
				0, '{EVENT.TAGS."Jira Id"}', 0, [
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{EVENT.TAGS."Jira Id"}',
					'parts' => ['EVENT', 'TAGS', '"Jira Id"']
				]
			],
			[
				0, '{EVENT.TAGS.""}.', 0, [
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{EVENT.TAGS.""}',
					'parts' => ['EVENT', 'TAGS', '""']
				]
			],
			[
				CEventTagsParser::TRIM_QUOTE_MARKS, '{EVENT.TAGS.""}.', 0, [
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{EVENT.TAGS.""}',
					'parts' => ['EVENT', 'TAGS', '']
				]
			],
			[
				0, '{EVENT.TAGS."{} \"\\\}"}', 0, [
					'rc' => CParser::PARSE_SUCCESS,
					'match' => '{EVENT.TAGS."{} \"\\\}"}',
					'parts' => ['EVENT', 'TAGS', '"{} \"\\\}"']
				]
			],
			[
				0, '{EVENT.TAGS.TEST', 0, [
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'parts' => []
				]
			],
			[
				0, '{EVENT.TAGS.', 0, [
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'parts' => []
				]
			],
			[
				0, '{EVENT.TAGS.""{} \"\\\}"}', 0, [
					'rc' => CParser::PARSE_FAIL,
					'match' => '',
					'parts' => []
				]
			]
		];
	}

	/**
	 * @dataProvider testProvider
	 *
	 * @param string $source
	 * @param int    $pos
	 * @param array  $expected
	*/
	public function testParse($options, $source, $pos, $expected) {
		$parser = new CEventTagsParser($options);

		$this->assertSame($expected, [
			'rc' => $parser->parse($source, $pos),
			'match' => $parser->getMatch(),
			'parts' => $parser->getParts()
		]);
		$this->assertSame(strlen($expected['match']), $parser->getLength());
	}
}
