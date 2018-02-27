;(function() {
	'use strict';

	BX.namespace('BX.Filter');

	BX.Filter.Utils = {
		cache: {},
		styleForEach: function(collection, properties)
		{
			var keys;
			properties = BX.type.isPlainObject(properties) ? properties : null;
			keys = Object.keys(properties);

			[].forEach.call((collection || []), function(current) {
				keys.forEach(function(propKey) {
					BX.style(current, propKey, properties[propKey]);
				});
			});
		},
		closestParent: function(item, className)
		{
			if (item)
			{
				if (!className)
				{
					return item.parentNode || null;
				}
				else
				{
					return BX.findParent(
						item,
						{class: className}
					);
				}
			}
		},
		closestChilds: function(item)
		{
			if (item) { return item.children || null; }
		},
		getNext: function(currentItem)
		{
			if (currentItem) { return currentItem.nextElementSibling || null; }
		},
		getPrev: function(currentItem)
		{
			if (currentItem) { return currentItem.previousElementSibling || null; }
		},
		collectionSort: function(current, target)
		{
			var root, collection, collectionLength, currentIndex, targetIndex;

			if (current && target && current !== target && current.parentNode === target.parentNode)
			{
				root = this.closestParent(target);
				collection = this.closestChilds(root);
				collectionLength = collection.length;
				currentIndex = this.getIndex(collection, current);
				targetIndex = this.getIndex(collection, target);

				if (collectionLength === targetIndex) {
					root.appendChild(target);
				}

				if (currentIndex > targetIndex) {
					root.insertBefore(current, target);
				}

				if (currentIndex < targetIndex && collectionLength !== targetIndex)
				{
					root.insertBefore(current, this.getNext(target));
				}
			}
		},
		getIndex: function(collection, item)
		{
			return [].indexOf.call((collection || []), item);
		},
		/**
		 * Gets elements by class name
		 * @param rootElement
		 * @param className
		 * @param first
		 * @returns {Array|null}
		 */
		getByClass: function(rootElement, className, all)
		{
			var result = [];

			if (className)
			{
				result = (rootElement || document.body).getElementsByClassName(className);

				if (!all)
				{
					result = result.length ? result[0] : null;
				}
				else
				{
					result = [].slice.call(result);
				}
			}

			return result;
		},

		getByTag: function(rootElement, tag, all)
		{
			var result = [];

			if (tag)
			{
				result = (rootElement || document.body).getElementsByTagName(tag);

				if (!all)
				{
					result = result.length ? result[0] : null;
				}
				else
				{
					result = [].slice.call(result);
				}
			}

			return result;
		},

		getBySelector: function(rootElement, selector, all)
		{
			var result = [];

			if (selector)
			{
				if (!all)
				{
					result = (rootElement || document.body).querySelector(selector);
				}
				else
				{
					result = (rootElement || document.body).querySelectorAll(selector);
					result = [].slice.call(result);
				}
			}

			return result;
		},

		requestAnimationFrame: function()
		{
			var raf = (
				window.requestAnimationFrame ||
				window.webkitRequestAnimationFrame ||
				window.mozRequestAnimationFrame ||
				window.msRequestAnimationFrame ||
				window.oRequestAnimationFrame ||
				function(callback){ window.setTimeout(callback, 1000/60) }
			);

			raf.apply(window, arguments);
		}
	};
})();