{
	"format_version": "1.10.0",
	"geometry.skins/suits": {
		"texturewidth": 64,
		"textureheight": 64,
		"visible_bounds_width": 3,
		"visible_bounds_height": 3,
		"visible_bounds_offset": [0, 0, 0],
		"bones": [
			{
				"name": "root",
				"pivot": [0, 0, 0]
			},
			{
				"name": "waist",
				"parent": "root",
				"pivot": [0, 12, 0]
			},
			{
				"name": "body",
				"parent": "waist",
				"pivot": [0, 24, 0],
				"cubes": [
					{"origin": [-4, 12, -2], "size": [8, 12, 4], "uv": [16, 16]}
				]
			},
			{
				"name": "head",
				"parent": "body",
				"pivot": [0, 24, 0],
				"cubes": [
					{"origin": [-4, 24, -4], "size": [8, 8, 8], "uv": [0, 0]}
				]
			},
			{
				"name": "hat",
				"parent": "head",
				"pivot": [0, 24, 0],
				"cubes": [
					{"origin": [-1, 24.65, -6.5], "size": [2, 1, 3], "uv": [24, 0]},
					{"origin": [-1, 23.65, -7.5], "size": [2, 1, 2], "uv": [24, 4]},
					{"origin": [-4.25, 28, -4.5], "size": [3, 2, 2], "uv": [54, 0]},
					{"origin": [-4.25, 27, -4.5], "size": [8.5, 1, 2], "uv": [34, 0]},
					{"origin": [1.25, 28, -4.5], "size": [3, 2, 2], "uv": [54, 0], "mirror": true},
					{"origin": [2.25, 30, -4.5], "size": [2, 1, 1], "uv": [58, 4], "mirror": true},
					{"origin": [-4.25, 30, -4.5], "size": [2, 1, 1], "uv": [58, 4], "mirror": true},
					{"origin": [-4.25, 23.75, -4.5], "size": [8.5, 3.25, 4], "uv": [34, 4], "mirror": true},
					{"origin": [-4.25, 24.75, -0.5], "size": [1, 2.25, 1], "uv": [60, 8], "mirror": true},
					{"origin": [3.25, 24.75, -0.5], "size": [1, 2.25, 1], "uv": [60, 8], "mirror": true}
				]
			},
			{
				"name": "cape",
				"parent": "body",
				"pivot": [0, 24, 3]
			},
			{
				"name": "leftArm",
				"parent": "body",
				"pivot": [5, 22, 0],
				"cubes": [
					{"origin": [4, 12, -2], "size": [4, 12, 4], "uv": [40, 16], "mirror": true}
				]
			},
			{
				"name": "leftSleeve",
				"parent": "leftArm",
				"pivot": [5, 22, 0],
				"cubes": [
					{"origin": [4, 12, -2], "size": [4, 12, 4], "uv": [48, 48], "inflate": 0.25}
				]
			},
			{
				"name": "leftItem",
				"parent": "leftArm",
				"pivot": [6, 15, 1]
			},
			{
				"name": "rightArm",
				"parent": "body",
				"pivot": [-5, 22, 0],
				"cubes": [
					{"origin": [-8, 12, -2], "size": [4, 12, 4], "uv": [40, 16]}
				]
			},
			{
				"name": "rightSleeve",
				"parent": "rightArm",
				"pivot": [-5, 22, 0],
				"cubes": [
					{"origin": [-8, 12, -2], "size": [4, 12, 4], "uv": [40, 32], "inflate": 0.25}
				]
			},
			{
				"name": "rightItem",
				"parent": "rightArm",
				"pivot": [-6, 15, 1],
				"locators": {
					"lead_hold": [-6, 15, 1]
				}
			},
			{
				"name": "jacket",
				"parent": "body",
				"pivot": [0, 24, 0],
				"cubes": [
					{"origin": [-4, 12, -2], "size": [8, 12, 4], "uv": [16, 32], "inflate": 0.25}
				]
			},
			{
				"name": "leftLeg",
				"parent": "root",
				"pivot": [1.9, 12, 0],
				"cubes": [
					{"origin": [-0.1, 0, -2], "size": [4, 12, 4], "uv": [0, 16], "mirror": true}
				]
			},
			{
				"name": "leftPants",
				"parent": "leftLeg",
				"pivot": [1.9, 12, 0],
				"cubes": [
					{"origin": [-0.1, 0, -2], "size": [4, 12, 4], "uv": [0, 48], "inflate": 0.25}
				]
			},
			{
				"name": "rightLeg",
				"parent": "root",
				"pivot": [-1.9, 12, 0],
				"cubes": [
					{"origin": [-3.9, 0, -2], "size": [4, 12, 4], "uv": [0, 16]}
				]
			},
			{
				"name": "rightPants",
				"parent": "rightLeg",
				"pivot": [-1.9, 12, 0],
				"cubes": [
					{"origin": [-3.9, 0, -2], "size": [4, 12, 4], "uv": [0, 32], "inflate": 0.25}
				]
			}
		]
	}
	}