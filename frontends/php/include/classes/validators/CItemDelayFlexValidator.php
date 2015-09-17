<?php
/*
** Zabbix
** Copyright (C) 2001-2015 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CItemDelayFlexValidator extends CValidator {

	private $time_period_validator;
	/**
	 * Returns true if the all of the given values and conditions match the criteria:
	 *
	 *		Flexible intervals must have valid delay and time period. Delay must not exceed 86400 seconds and
	 *		period should correspond to time period syntax %d-%d,%d:%d-%d:%d or %d,%d:%d-%d:%d
	 *		which is validated by CTimePeriodValidator.
	 *
	 *		Scheduling intervals must have valid month days 1-31, week days 1-7, hours 0-23, minutes 0-59
	 *		and seconds 0-59. Second month day, week day, hour, minute or second should not be greater than
	 *		first.
	 *
	 * @param array $intervals						An array of intervals to validate.
	 * @param string $intervals[]['type']			Interval type: flexible or scheduling.
	 *
	 * @return bool
	 */
	public function validate($intervals) {
		if (!$intervals) {
			return true;
		}

		$this->time_period_validator = new CTimePeriodValidator();

		$result = true;

		foreach ($intervals as $interval) {
			if ($interval['type'] == ITEM_DELAY_FLEX_TYPE_FLEXIBLE) {
				$result = $result && $this->validateFlexibleInterval($interval);
			}
			elseif ($interval['type'] == ITEM_DELAY_FLEX_TYPE_SCHEDULING) {
				$result = $result && $this->validateSchedulingInterval($interval);
			}
		}

		return $result;
	}

	/**
	 * Validate flexible interval delay and time period.
	 *
	 * @param array $interval						Data of flexible interval array.
	 * @param string $interval[]['delay']			Flexible interval delay.
	 * @param string $interval[]['period']			Flexible interval period.
	 *
	 * return bool
	 */
	private function validateFlexibleInterval($interval) {
		if ($interval['delay'] > SEC_PER_DAY) {
			$this->setError(_s('Invalid flexible interval delay: "%1$s" exceeds maximum delay of "%2$s".',
				$interval['delay'],
				SEC_PER_DAY
			));

			return false;
		}

		if ($this->time_period_validator->validate($interval['period'])) {
			return true;
		}
		else {
			$this->setError($this->time_period_validator->getError());

			return false;
		}
	}

	/**
	 * Validate scheduling interval month days, week days, hours, minutes and seconds.
	 *
	 * @param array $interval						Data of scheduling interval array.
	 * @param string $interval[]['interval']		An array containing month days, week days, hours, minutes, seconds.
	 * @param array $interval[]['md']				An array of month month days.
	 * @param string $interval[]['md'][]['from']	Month day "from".
	 * @param string $interval[]['md'][]['till']	Month day "till".
	 * @param string $interval[]['md'][]['step']	Month "step".
	 * @param array $interval[]['wd']				An array of week days.
	 * @param string $interval[]['wd'][]['from']	Week "from".
	 * @param string $interval[]['wd'][]['till']	Week "till".
	 * @param string $interval[]['wd'][]['step']	Week "step".
	 * @param array $interval[]['h']				An array of hours.
	 * @param string $interval[]['h'][]['from']		Hours "from".
	 * @param string $interval[]['h'][]['till']		Hours "till".
	 * @param string $interval[]['h'][]['step']		Hours step
	 * @param array $interval[]['m']				An array of minutes.
	 * @param string $interval[]['m'][]['from']		Minutes "from".
	 * @param string $interval[]['m'][]['till']		Minutes "till".
	 * @param string $interval[]['m'][]['step']		Minutes "step".
	 * @param array $interval[]['s']				An array of seconds.
	 * @param string $interval[]['s'][]['from']		Seconds "from".
	 * @param string $interval[]['s'][]['till']		Seconds "till".
	 * @param string $interval[]['s'][]['step']		Seconds "step".
	 *
	 * return bool
	 */
	private function validateSchedulingInterval($interval) {
		// Check month day boundaries.
		if (array_key_exists('md', $interval)) {
			foreach ($interval['md'] as $month) {
				if ($month['from'] !== '') {
					$month_from = (int) $month['from'];

					if ($month_from < 1 || $month_from > 31) {
						$this->setError(_s('Invalid interval "%1$s": invalid month day "%2$s".', $interval['interval'],
							$month['from']
						));
						return false;
					}

					// Ending month day is optional.
					if ($month['till'] !== '') {
						$month_till = (int) $month['till'];

						// Should be a valid month day.
						if ($month_till < 1 || $month_till > 31) {
							$this->setError(_s('Invalid interval "%1$s": invalid month day "%2$s".',
								$interval['interval'],
								$month['till']
							));
							return false;
						}

						// If entered, it cannot be greater than starting month day.
						if ($month_from > $month_till) {
							$this->setError(_s(
								'Invalid interval "%1$s": starting month day must be less or equal to ending month day.',
								$interval['interval']
							));
							return false;
						}

						// Month step is optional.
						if ($month['step'] !== '') {
							$month_step = (int) $month['step'];

							if ($month_step < 1 || $month_step > 30
									|| ($month_step > ($month_till - $month_from))
									|| ($month_from == $month_till && $month_step != 1)) {
								$this->setError(_s('Invalid interval "%1$s": invalid month step "%2$s".',
									$interval['interval'],
									$month['step']
								));
								return false;
							}
						}
					}
				}
				elseif ($month['step'] !== '') {
					$month_step = (int) $month['step'];

					// If month day is ommited, month step is mandatory.
					if ($month_step < 1 || $month_step > 30) {
						$this->setError(_s('Invalid interval "%1$s": invalid month step "%2$s".', $interval['interval'],
							$month['step']
						));
						return false;
					}
				}
			}
		}

		// Check week day boundaries.
		if (array_key_exists('wd', $interval)) {
			foreach ($interval['wd'] as $week) {
				if ($week['from'] !== '') {
					$week_from = (int) $week['from'];

					if ($week_from < 1 || $week_from > 7) {
						$this->setError(_s('Invalid interval "%1$s": invalid week day "%2$s".', $interval['interval'],
							$week['from']
						));
						return false;
					}

					// Ending week day is optional.
					if ($week['till'] !== '') {
						$week_till = (int) $week['till'];

						// Should be a valid week day.
						if ($week_till < 1 || $week_till > 7) {
							$this->setError(_s('Invalid interval "%1$s": invalid week day "%2$s".',
								$interval['interval'],
								$week['till']
							));
							return false;
						}

						// If entered, it cannot be greater than starting week day.
						if ($week_from > $week_till) {
							$this->setError(_s(
								'Invalid interval "%1$s": starting week day must be less or equal to ending week day.',
								$interval['interval']
							));
							return false;
						}

						// Week step is optional.
						if ($week['step'] !== '') {
							$week_step = (int) $week['step'];

							if ($week_step < 1 || $week_step > 6
									|| ($week_step > ($week_till - $week_from))
									|| ($week_from == $week_till && $week_step != 1)) {
								$this->setError(_s('Invalid interval "%1$s": invalid week step "%2$s".',
									$interval['interval'],
									$week['step']
								));
								return false;
							}
						}
					}
				}
				elseif ($week['step'] !== '') {
					$week_step = (int) $week['step'];

					// If week day is ommited, week step is mandatory.
					if ($week_step < 1 || $week_step > 6) {
						$this->setError(_s('Invalid interval "%1$s": invalid week step "%2$s".', $interval['interval'],
							$week['step']
						));
						return false;
					}
				}
			}
		}

		// Check hour boundaries.
		if (array_key_exists('h', $interval)) {
			foreach ($interval['h'] as $hour) {
				if ($hour['from'] !== '') {
					$hour_from = (int) $hour['from'];

					if ($hour_from > 23) {
						$this->setError(_s('Invalid interval "%1$s": invalid hours "%2$s".', $interval['interval'],
							$hour['from']
						));
						return false;
					}

					// Ending hour is optional.
					if ($hour['till'] !== '') {
						$hour_till = (int) $hour['till'];

						// Should be a valid hour.
						if ($hour_till > 23) {
							$this->setError(_s('Invalid interval "%1$s": invalid hours "%2$s".', $interval['interval'],
								$hour['till']
							));
							return false;
						}

						// If entered, it cannot be greater than starting hour.
						if ($hour_from > $hour_till) {
							$this->setError(_s(
								'Invalid interval "%1$s": starting hours must be less or equal to ending hours.',
								$interval['interval']
							));
							return false;
						}

						// Hour step is optional.
						if ($hour['step'] !== '') {
							$hour_step = (int) $hour['step'];

							if ($hour_step < 1 || $hour_step > 23
									|| ($hour_step > ($hour_till - $hour_from))
									|| ($hour_from == $hour_till && $hour_step != 1)) {
								$this->setError(_s('Invalid interval "%1$s": invalid hours step "%2$s".',
									$interval['interval'],
									$hour['step']
								));
								return false;
							}
						}
					}
				}
				elseif ($hour['step'] !== '') {
					$hour_step = (int) $hour['step'];

					// If hour is ommited, hour step is mandatory.
					if ($hour_step < 1 || $hour_step > 23) {
						$this->setError(_s('Invalid interval "%1$s": invalid hours step "%2$s".', $interval['interval'],
							$hour['step']
						));
						return false;
					}
				}
			}
		}

		// Check minute boundaries.
		if (array_key_exists('m', $interval)) {
			foreach ($interval['m'] as $minute) {
				if ($minute['from'] !== '') {
					$minute_from = (int) $minute['from'];

					if ($minute_from > 59) {
						$this->setError(_s('Invalid interval "%1$s": invalid minutes "%2$s".', $interval['interval'],
							$minute['from']
						));
						return false;
					}

					// Ending minute is optional.
					if ($minute['till'] !== '') {
						$minute_till = (int) $minute['till'];

						// Should be a valid minute.
						if ($minute_till > 59) {
							$this->setError(_s('Invalid interval "%1$s": invalid minutes "%2$s".', $interval['interval'],
								$minute['till']
							));
							return false;
						}

						// If entered, it cannot be greater than starting minute.
						if ($minute_from > $minute_till) {
							$this->setError(_s(
								'Invalid interval "%1$s": starting minutes must be less or equal to ending minutes.',
								$interval['interval']
							));
							return false;
						}

						// Minute step is optional.
						if ($minute['step'] !== '') {
							$minute_step = (int) $minute['step'];

							if ($minute_step < 1 || $minute_step > 59
									|| ($minute_step > ($minute_till - $minute_from))
									|| ($minute_from == $minute_till && $minute_step != 1)) {
								$this->setError(_s('Invalid interval "%1$s": invalid minutes step "%2$s".',
									$interval['interval'],
									$minute['step']
								));
								return false;
							}
						}
					}
				}
				elseif ($minute['step'] !== '') {
					$minute_step = (int) $minute['step'];

					// If minute is ommited, minute step is mandatory.
					if ($minute_step < 1 || $minute_step > 59) {
						$this->setError(_s('Invalid interval "%1$s": invalid minutes step "%2$s".',
							$interval['interval'],
							$minute['step']
						));
						return false;
					}
				}
			}
		}

		// Check minute boundaries.
		if (array_key_exists('s', $interval)) {
			foreach ($interval['s'] as $second) {
				if ($second['from'] !== '') {
					$second_from = (int) $second['from'];

					if ($second_from > 59) {
						$this->setError(_s('Invalid interval "%1$s": invalid seconds "%2$s".', $interval['interval'],
							$second['from']
						));
						return false;
					}

					// Ending second is optional.
					if ($second['till'] !== '') {
						$second_till = (int) $second['till'];

						// Should be a valid second.
						if ($second_till > 59) {
							$this->setError(_s('Invalid interval "%1$s": invalid seconds "%2$s".', $interval['interval'],
								$second['till']
							));
							return false;
						}

						// If entered, it cannot be greater than starting second.
						if ($second_from > $second_till) {
							$this->setError(_s(
								'Invalid interval "%1$s": starting seconds must be less or equal to ending seconds.',
								$interval['interval']
							));
							return false;
						}

						// Second step is optional.
						if ($second['step'] !== '') {
							$second_step = (int) $second['step'];

							if ($second_step < 1 || $second_step > 59
									|| ($second_step > ($second_till - $second_from))
									|| ($second_from == $second_till && $second_step != 1)) {
								$this->setError(_s('Invalid interval "%1$s": invalid seconds step "%2$s".',
									$interval['interval'],
									$second['step']
								));
								return false;
							}
						}
					}
				}
				elseif ($second['step'] !== '') {
					$second_step = (int) $second['step'];

					// If second is ommited, second step is mandatory.
					if ($second_step < 1 || $second_step > 59) {
						$this->setError(_s('Invalid interval "%1$s": invalid seconds step "%2$s".',
							$interval['interval'],
							$second['step']
						));
						return false;
					}
				}
			}
		}

		return true;
	}
}
