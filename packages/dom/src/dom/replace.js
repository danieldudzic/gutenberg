/**
 * Internal dependencies
 */
import insertAfter from './insert-after';
import remove from './remove';

/**
 * Given two DOM nodes, replaces the former with the latter in the DOM.
 *
 * @param {Element} processedNode Node to be removed.
 * @param {Element} newNode       Node to be inserted in its place.
 * @return {void}
 */
export default function replace( processedNode, newNode ) {
	insertAfter( newNode, processedNode.parentNode );
	remove( processedNode );
}
