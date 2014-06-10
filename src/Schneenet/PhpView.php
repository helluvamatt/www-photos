<?php

namespace Schneenet;

use Slim\View;
use Slim\Helper\Set;
use Slim\Slim;

/*
 * Mimic CodeIgniter View behavior
 * @DEPRECTATED - This class does not implement CI "helpers" which are necessary to template functionality, use Twig instead
 */
class PhpView extends View
{
	
	private $template_dir;
	
	function __construct(Slim $app) {
		$this->app = $app;
		$this->data = new Set();
		$this->data->set('title', 'Photos');
		$this->data->set('content', '');
	}
	
	public function render($template) {
		/*
		 * Mimic CodeIgniter behaviour for using PHP templates:
		 * 1. $data hash: keys become variable names, values become the variable value, see extract()
		 * 2. Load some helper functions
		 * 3. Start an output buffer
		 * 4. include() the template. It will be executed like any other PHP file, with the scope right here
		 * 5. Save the contents of the output buffer and end the buffer
		 * 6. Return the buffer contents
		 */
		extract($this->data->all(), EXTR_SKIP);
		
		ob_start();
		include($this->getTemplatePathname($template));
		$buffer = ob_get_contents();
		@ob_end_clean();
		return $buffer;
	}
	
	protected function urlFor($name, $params = array())
	{
		return $this->app->urlFor($name, $params);
	}

}