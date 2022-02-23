<?php namespace spitfire\model\relations;

use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\Join;
use spitfire\storage\database\query\JoinTable;

/**
 * The belongsTo relationship allows an application to indicate that this
 * model is part of a 1:n relationship with another model.
 *
 * In this case, the model using this relationship is the n part or the
 * child. This makes it a single relationship, since models using this
 * relationship will have a single parent.
 */
class BelongsTo extends Relationship implements RelationshipSingleInterface
{
	
}
