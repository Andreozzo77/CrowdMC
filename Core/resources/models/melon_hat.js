{
	"format_version": "1.10.0",
	"geometry.melon_hat": {
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
					{"origin": [-4, 24, -4], "size": [8, 8, 8], "uv": [32, 0], "inflate": 0.5}
				]
			},
			{
				"name": "melon",
				"parent": "head",
				"pivot": [0, 24, 0],
				"cubes": [
					{"origin": [-3.75, 24.5, -5], "size": [7.5, 5, 1], "uv": [0, 2]},
					{"origin": [-1.5, 29.5, -5], "size": [5.25, 1, 1], "uv": [18, 8]},
					{"origin": [-0.5, 30.5, -5], "size": [4.25, 1, 1], "uv": [20, 14]},
					{"origin": [3.75, 31.5, -3.75], "size": [1.25, 1.5, 7.5], "uv": [0, 8]},
					{"origin": [1.25, 31.5, -5], "size": [2.5, 1.5, 6.25], "uv": [16, 9]},
					{"origin": [-3.75, 24.5, 4], "size": [7.5, 7, 1], "uv": [0, 0]},
					{"origin": [4, 24.5, -3.75], "size": [1, 7, 7.5], "uv": [16, 0]},
					{"origin": [3.75, 24.5, 3.75], "size": [1.25, 7, 1.25], "uv": [22, 0]},
					{"origin": [3.75, 24.5, -5], "size": [1.25, 7, 1.25], "uv": [22, 0]},
					{"origin": [-5, 24.5, -5], "size": [1.25, 5, 1.25], "uv": [22, 0]},
					{"origin": [-5, 24.5, 3.75], "size": [1.25, 7, 1.25], "uv": [22, 0]},
					{"origin": [-5, 24.5, -3.75], "size": [1, 5, 7.5], "uv": [16, 0]},
					{"origin": [-5, 29.5, -1.5], "size": [1, 1, 5.25], "uv": [20, 10]},
					{"origin": [-5, 30.5, -0.5], "size": [1, 1, 4.25], "uv": [22, 10]},
					{"origin": [-5, 31.5, 1.25], "size": [6.25, 1.5, 2.5], "uv": [16, 13]},
					{"origin": [1.25, 31.5, 1.25], "size": [2.5, 1.5, 2.5], "uv": [24, 0]},
					{"origin": [-0.75, 31.5, 0.25], "size": [2, 1.5, 1], "uv": [16, 0]},
					{"origin": [0.25, 31.5, -0.75], "size": [1, 1.5, 1], "uv": [15, 0]},
					{"origin": [-3.75, 31.5, 3.75], "size": [7.5, 1.5, 1.25], "uv": [0, 14]},
					{"origin": [-3.5, 23, -3.5], "size": [7, 1, 7], "uv": [2, 0]},
					{"origin": [-3.5, 23, -5], "size": [7, 1.5, 1.5], "uv": [0, 14]},
					{"origin": [-3.5, 23, 3.5], "size": [7, 1.5, 1.5], "uv": [0, 14]},
					{"origin": [3.5, 23, -3.5], "size": [1.5, 1.5, 7], "uv": [0, 8]},
					{"origin": [-5, 23, -3.5], "size": [1.5, 1.5, 7], "uv": [0, 8]}
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
					{"origin": [4, 12, -2], "size": [4, 12, 4], "uv": [32, 48]}
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
					{"origin": [-0.1, 0, -2], "size": [4, 12, 4], "uv": [16, 48]}
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