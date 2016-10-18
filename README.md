# PageBuilder

WordPress plugin that lets you design page content using drag and drop modules.

## Module Settings

#### `name`

Name of the module. It is shown in the module header.

#### `ref`

Unique reference of the module.

#### `desc (optional)`

Description of the module.

#### `type`

Type of module.

- container
- content
- static (Ignores content)

#### `before`

HTML rendered before the module content.

#### `after`

HTML rendered after the module content.

#### `width`

How many columns the module takes up.

- 2
- 4
- 6
- 8
- 12

#### `private (optional)`

Whether or not you have access to this. Setting to true prevents it from showing in the modules bar, and you cannot move these around.

- true
- false (default)

#### `locked`

Whether you can drop additional modules into the module. No effect for content modules.

- true
- false (default)

#### `mode (optional)`

Which way the content of the content block should be interpretated.

- text (default) (Converts new lines to <br>)
- markdown (Compiles content via markdown)
- code (Raw content allowed including HTML and JS)

## Group Only Settings

#### `content`

The content of the group module. Example:

```
"content": [{
	"ref": 		"row",
	"content": [{
		"ref":		"col6",
		"content": 	[{}]
	},
	{
		"ref":		"col6",
		"content": 	[{}]
	}]
}]
```