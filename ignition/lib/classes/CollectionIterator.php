<?php
/*
 * CollectionIterator
 * =================
 *
 * Author:      David Marrs
 * Copyright:   Deveus Software, 2006. All rights reserved.
 * Description: Iterates through a collection of objects
 *              returning the same desired property of each.
 */

class CollectionIterator
{
    var $Collection;    # The collection being iterated
    var $Property;      # The property being fetched
    var $Stack;
    var $Position;
    var $Last;
    var $Current;

    function CollectionIterator(&$collection, $property){
        $this->Collection = $collection;
        $this->Property = $property;
        $this->Stack = array();
        $this->Position = 0;
    }

    function Next(){
        $this->Last = $this->Collection->Collection[$this->Position]->{$this->Property};
        $this->Current = $this->Collection->Collection[++$this->Position]->{$this->Property};
        return $this->Current;
    }
}
?>