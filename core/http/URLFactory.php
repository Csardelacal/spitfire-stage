<?php namespace spitfire\core\http;

use spitfire\collection\Collection;
use spitfire\core\Request;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

/**
 * The URL factory allows us to create URLs, passing some predefined parameters,
 * like which router they should use to use to reverse routes, what base url to
 * use and similar configuration.
 */
class URLFactory
{
	
	private $request;
	private $routes;
	
	public function __construct(Request $request, Collection $routes)
	{
		$this->request = $request;
		$this->routes = $routes;
	}
	
	/**
	 * Returns a formatted version of the current URL.
	 */
	public function current() : string
	{
		
	}
	
	/**
	 * Performs the same operations as current, but removes get parameters that are not being
	 * used by the application in the query string.
	 */
	public function canonical() : string
	{
		
	}
	
	/**
	 * Generates a url from a named route. You just provide the name of the route, and the application
	 * will find the path and appropriately interpolate the parameters.
	 */
	public function named(string $name, array $parameters, array $query) : string
	{
		
	}
	
	/**
	 * Generates a URL from an anonymous path within the application and appends the query data in the
	 * second parameter to generate a proper output.
	 */
	public function anonymous(string $path, array $query) : string
	{
		
	}
	
}