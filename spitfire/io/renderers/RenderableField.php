<?php namespace spitfire\io\renderers;

/**
 * Classes implementing this interface indicate that they can be used to generate
 * html to receive data from a user and store it by their own means.
 * 
 * This interfaces are only used to indicate the renderer what datatype the classes
 * that implement this expect and generate it accordingly. In order for this to
 * work porperly the renderer and this classes must integrate properly.
 * 
 */
interface RenderableField extends Renderable
{
	/**
	 * Gets the value that this field contains. Note that the renderer may or may not 
	 * override this with the post data and should not do so. This means you should 
	 * present the data the way you want (including the requested value) when returning
	 * this.
	 */
	abstract public function getValue();
}