;(function() {
	'use strict';

	BX.namespace('BX');

	var isBlock = function(block)
	{
		return BX.type.isPlainObject(block) && ('block' in block);
	};

	var isString = function(string)
	{
		return BX.type.isNotEmptyString(string);
	};

	var isTag = function(tag)
	{
		return BX.type.isPlainObject(tag) && !('block' in tag && 'elem' in tag);
	};

	BX.render = function(item)
	{
		var element = null;
		var tag, className, attrs, props, text, children;

		if (isBlock(item) || isTag(item))
		{
			tag = 'tag' in item ? item.tag : 'div';
			className = item.block;
			attrs = 'attrs' in item ? item.attrs : {};
			props = {};

			if ('props' in item && BX.type.isPlainObject(item.props))
			{
				props = item.props;
			}

			if ('mix' in item && BX.type.isArray(item.mix))
			{
				item.mix.push(className);
				props.className = item.mix.join(' ');
			}
			else
			{
				props.className = className;
			}

			if ('content' in item)
			{
				if (isBlock(item.content) || isTag(item.content))
				{
					if (item.content.block in BX.Main.ui.block)
					{
						item.content = BX.Main.ui.block[item.content.block](item.content);
					}

					children = [BX.render(item.content)];
				}

				if (isString(item.content))
				{
					text = BX.util.htmlspecialchars(item.content);
				}

				if (BX.type.isArray(item.content))
				{
					children = BX.decl(item.content);
				}
			}

			element = BX.create(tag, {props: props, attrs: attrs, children: children, html: text});
		}
		else if (isString(item))
		{
			element = BX.util.htmlspecialchars(item);
		}

		return element;
	};


	BX.decl = function(decl)
	{
		var result = null;

		if (BX.type.isArray(decl))
		{
			result = decl.map(function(current) {
				if (isBlock(current) && current.block in BX.Main.ui.block)
				{
					current = BX.Main.ui.block[current.block](current);
				}

				return BX.render(current);
			});
		}
		else if (isBlock(decl))
		{
			if (decl.block in BX.Main.ui.block)
			{
				decl = BX.Main.ui.block[decl.block](decl);
			}

			result = BX.render(decl);
		}

		return result;
	};
})();