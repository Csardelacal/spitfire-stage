<?php namespace spitfire\mvc\middleware\standard;

use ArrayAccess;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\collection\Collection;
use spitfire\ast\Scope;
use spitfire\core\ContextInterface;
use spitfire\core\Response;
use spitfire\exceptions\ApplicationException;
use spitfire\provider\Container;
use spitfire\validation\ValidationException;
use spitfire\validation\parser\Parser;
use spitfire\validation\ValidationRule;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class ValidationMiddleware implements MiddlewareInterface
{
	
	/**
	 * 
	 * @var RequestHandlerInterface|null
	 */
	private $response;
	
	/**
	 * 
	 * @var Collection<ValidationRule>
	 */
	private $rules;
	
	/**
	 * The middleware needs access to the container, so that we can interact with
	 * other components of the system appropriately.
	 * 
	 * @var Container
	 */
	private $container;
	
	public function __construct(Container $container, Collection $rules, ?RequestHandlerInterface $errorpage)
	{
		$this->container = $container;
		$this->rules = $rules;
		$this->response = $errorpage;
		
		assert($rules->containsOnly(ValidationRule::class));
	}
	
	/**
	 * Handle the request, performing validation. 
	 * 
	 * @todo If the validation fails, the information should be injected into view, so the application can use it
	 * @todo Introduce a class that maintains the list of validation errors so controllers can locate them
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$body = $request->getParsedBody()?: [];
		
		/**
		 * Check that the body is actually set and an array or ArrayAccess object. We don't support
		 * any other mechanism for the validation. Please note that this will not be checked in
		 * production. Your application needs to work properly when it leaves development.
		 */
		assert(is_array($body) || $body instanceof ArrayAccess);
		
		$errors = $this->rules->each(function (ValidationRule $rule, string $key) {
			return $rule->test($body[$key]?? null);
		})->filter();
		
		/**
		 * If there's errors, and there's a special handler defined for error pages, then we 
		 * send the user to the appropriate page.
		 * 
		 * Here's where I'd recommend introduce a flasher handler that would redirect the user
		 * to the form page and have the data they sent us resubmitted, allowing it to pretend
		 * it is a get request, using a "_method" hidden input.
		 */
		if (!$errors->isEmpty()) {
			
			/**
			 * If a response is provided by the developer, we can continue using that.
			 */
			if ($this->response) {
				return $this->response->handle($request);
			}
			
			/**
			 * If the client expects a json response, we will send a json response with the error validation
			 */
			elseif ($request->hasHeader('accept') && $request->getHeader('accept')[0] === 'application/json') {
				return response(
					view(null, ['status' => 'failed', 'errors' => $errors]), 
					200, 
					['Content-Type' => ['application/json']]
				);
			}
			
			/**
			 * If the client is sending the data via post, and is expecting to be redirected, we will send them
			 * back to the page that delivered them to us.
			 */
			elseif ($request->hasHeader('referrer')) {
				return response(
					view('_error/validation.html', ['errors' => $errors, 'submitted' => $request->getParsedBody(), 'location' => $request->getHeader('referrer')[0]])
				);
			}
			
			/**
			 * Without a referrer we can't reliably redirect the user to a location to retry entering the data 
			 * properly.
			 */
			else {
				throw new ApplicationException('Validation failed');
			}
		} 
		
		/**
		 * If the errors are empty, or we just do want the controller to handle them in an explicit
		 * manner, we can let the application do so.
		 */
		return $handler->handle($request);
	}

}
