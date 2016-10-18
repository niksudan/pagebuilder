<?php

require_once('lib/Parsedown.php');

class PageBuilder
{
	public $data = null;
	public $modules = array();
	public $groups = array();
	public $timesUsed = array();

	const ENABLE_JSON_SAVE = false;

	/**
	 * Constructor
	 * 
	 * @param string $fpath
	 * @param array $data
	 */
	public function __construct($fpath = null)
	{
		if ($fpath == null) {
			$fpath = __DIR__ . '/modules.json';
		}
		$modules = json_decode(file_get_contents($fpath));

		// Parse modules
		foreach ($modules->modules as $module) {
			if (!isset($module->private)) {
				$module->private = 'false';
			}
			if (!isset($module->locked)) {
				$module->locked = 'false';
			}
			if (!isset($module->desc)) {
				$module->desc = '';
			}
			if (!isset($module->mode)) {
				$module->mode = 'text';
			}
			$this->modules[$module->ref] = $module;
		}

		// Parse groups
		foreach ($modules->groups as $group) {
			if (!isset($group->private)) {
				$group->private = 'false';
			}
			if (!isset($group->locked)) {
				$group->locked = 'false';
			}
			if (!isset($group->desc)) {
				$group->desc = '';
			}
			if (!isset($group->mode)) {
				$group->mode = 'text';
			}
			$this->groups[$group->ref] = $group;
		}
	}

	/**
	 * Loads page data from json
	 * 
	 * @param string $data
	 */
	public function loadData($data)
	{
		$this->data = json_decode($data, true);
	}

	/**
	 * Get a module by reference
	 * 
	 * @param string $ref
	 * @return object/false
	 */
	public function getModule($ref)
	{
		// Increase times used count
		if (!isset($this->timesUsed[$ref])) {
			$this->timesUsed[$ref] = 0;
		}
		$this->timesUsed[$ref]++;

		// Is it a module?
		if (isset($this->modules[$ref])) {
			return $this->modules[$ref];

		// Is it a group?
		} elseif (isset($this->groups[$ref])) {
			return $this->groups[$ref];
		}

		return false;
	}

	/**
	 * Render a page's HTML from data
	 * 
	 * @param string $data
	 * @return string
	 */
	public function render($data = null)
	{
		$output = PHP_EOL;

		if ($data === null) {
			$output = '';
			$data = $this->data;
		}

		// Cycle through children
		if (!empty($data)) {
			foreach ($data as $index => $entry) {
				$module = $this->getModule($entry['ref']);
				if ($module && isset($entry['content'])) {

					$content = '';

					// Container
					if (is_array($entry['content']) && $module->type == 'container') {
						$content = $this->render($entry['content']);

					// Content
					} elseif ($module->type == 'content') {
						$content = stripslashes($entry['content']);
						switch ($module->mode) {
							case 'markdown':
								$pd = new Parsedown();
								$content = $pd->text($content);
								break;
							case 'code':
								$content = trim($content);
								break;
							case 'text':
							default:
								$content = nl2br(htmlentities($content));
						}
					}

					$output .= $module->before . $content . $module->after . PHP_EOL;

				} elseif ($module->type == 'static') {
					$output .= $module->before . $module->after . PHP_EOL;
				}
			}
		}

		return $output;
	}

	/**
	 * Render a page's HTML for admin layout from data
	 * 
	 * @param string $data
	 * @return string
	 */
	public function renderAdmin($data = null, $locked = false)
	{
		$output = '';
		$wrapper = false;

		// Output header if top level
		if ($data === null) {
			$wrapper = true;
			$output = '<div id="pbAdmin" class="clearfix"><ul class="layout layout-locked clearfix">';
			$output .= '<li name="Modules" ref="modules" w="12" id="pbModules" class="parentContainer">' . $this->renderModulesBar() . '</li>';
			$output .= '<li name="Page" ref="page" w="12" id="pbLayout" class="parentContainer">';
			$data = $this->data;
		}
		$output .= '<ul class="layout' . ($locked === 'true' ? ' layout-locked' : '') . ' clearfix">';

		// Cycle through children
		if (isset($data)) {
			foreach ($data as $index => $entry) {
				if (!empty($entry)) {

					// Grab module data
					$module = $this->getModule($entry['ref']);
					if ($module && isset($entry['content'])) {

						// Container
						if (is_array($entry['content']) && $module->type == 'container') {
							$content = $this->renderAdmin($entry['content'], $module->locked);

						// Content
						} elseif ($module->type == 'content') {
							$content = '<textarea class="form-control pbmode-' . $module->mode . '">' . $entry['content'] . '</textarea>';
						
						// Static
						} elseif ($module->type == 'static') {
							$content = 'Predefined content';
						}

						$output .= '<li w="' . $module->width . '" name="' . $module->name . '" ref="' . $module->ref . '" private="' . $module->private .'" type="' . $module->type . '"><div class="content">' . $content . '</div></li>' . PHP_EOL;
					}
				}
			}
		}

		$output .= '</ul>';

		// Output footer if top level
		if ($wrapper) {
			$output .= '</li></ul></div>';
			if (self::ENABLE_JSON_SAVE) {
				$output .= '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '"><hr><input id="pbData" type="hidden" name="data" value=""><input type="submit" id="pbSave" class="btn btn-success btn-block" value="Save"></form>';
			} else {
				$output .= '<input id="pbData" type="hidden" name="pb_data" value="">';
			}
		}

		return $output;
	}

	/**
	 * Render the list of modules
	 * 
	 * @return string
	 */
	private function renderModulesBar()
	{
		$output = '<legend class="title">Modules</legend><ul class="layout layout-locked layout-modules clearfix">';

		// Groups
		foreach ($this->groups as $group) {
			$data = json_decode(json_encode($group->content), true);
			$content = $this->renderAdmin($data, $group->locked);
			$desc = (!empty($group->desc) ? '<span class="desc">' . $group->desc . '</span>' : '' );
			$output .= '<li w="' . $group->width . '" name="' . $group->name . '" ref="' . $group->ref . '" private="' . $group->private . '" type="' . $group->type . '" class="module">' . $desc . '<div class="content">' . $content . '</div></li>';
		}

		// Modules
		foreach ($this->modules as $module) {
			if ($module->private == 'false') {
				$desc = (!empty($module->desc) ? '<span class="desc">' . $module->desc . '</span>' : '' );

				// Content
				if ($module->type == 'content') {
					$content = '<textarea class="form-control pbmode-' . $module->mode . '"></textarea>';

				// Container
				} elseif ($module->type == 'container') {
					$content = '<ul class="layout' . ($module->locked === 'true' ? ' layout-locked' : '') . ' clearfix"></ul>';
				
				// Static
				} elseif ($module->type == 'static') {
					$content = 'Predefined content';
				}

				$output .= '<li w="' . $module->width . '" name="' . $module->name . '" ref="' . $module->ref . '" private="' . $module->private . '" type="' . $module->type . '" class="module">' . $desc . '<div class="content">' . $content . '</div></li>';
			}
		}

		$output .= '</ul>';
		return $output;
	}

	/**
	 * Save the data
	 * 
	 * @param array $data
	 * @param string $fname
	 * @return string .json file with data
	 */
	public function save($data, $fname)
	{
		$this->loadData(json_encode($data));
		$fpath = __DIR__ . '/data/' . $fname . '.json';
		file_put_contents($fpath, stripslashes($this->data));
		return $fpath;
	}
}