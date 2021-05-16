{
	"format_version": "1.10.0",
	"geometry.tv_hat": {
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
				"name": "tv",
				"parent": "head",
				"pivot": [0, 24, 0],
				"cubes": [
					{"origin": [-5, 24.65, 2.2], "size": [1, 1, 2.8], "uv": [0, 5]},
					{"origin": [-5, 27.05, 2.2], "size": [1, 1, 2.8], "uv": [0, 5]},
					{"origin": [-5, 24.65, 4], "size": [2.7, 1, 1], "uv": [0, 6]},
					{"origin": [-5, 27.05, 4], "size": [2.7, 1, 1], "uv": [0, 6]},
					{"origin": [-3.4, 25.65, 4], "size": [1.1, 1.4, 1], "uv": [0, 6]},
					{"origin": [0, 24.55, 4], "size": [1.1, 5.7, 1], "uv": [17, 6]},
					{"origin": [1.2, 28.05, 4], "size": [1.1, 1.1, 1], "uv": [17, 5]},
					{"origin": [2.3, 26.95, 4], "size": [1.1, 1.1, 1], "uv": [17, 5]},
					{"origin": [-5, 25.65, 2.2], "size": [1.1, 1.4, 1], "uv": [0, 6]},
					{"origin": [-5.05, 24.15, -5.05], "size": [10.05, 1.4, 1], "uv": [0, 2]},
					{"origin": [3.95, 25.55, -5.05], "size": [1.05, 6, 3.9], "uv": [23, 3]},
					{"origin": [-5.05, 25.55, -5.05], "size": [1.05, 6, 3.9], "uv": [23, 3]},
					{"origin": [-5.05, 31.55, -5.05], "size": [10.05, 1.4, 3.9], "uv": [0, 12]},
					{"origin": [-3.95, 31.95, -1.15], "size": [7.85, 1, 1.2], "uv": [0, 9]}
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