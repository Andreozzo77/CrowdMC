{
    "Premium Ranks": {
        "Vip": {
            "description": "This is the Vip rank. Its features include...",
            "features": [
                "Bigger, brighter chats!",
                "Set the \/time on all worlds!",
                "Unlock the \/vip command. You can go to the VIP world, get discounts in the shop, and use the premium mine!",
                "Money bonus for every kill you make!",
                "View the coordinates of the \/lastenvoy that spawned!",
                "Teleport to your last death location with \/back!",
                "Permission to use your rank \/kit!"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/B4500E5FDD1B4608897A.png",
            "price": 5,
            "instantCommands": [
                "setgroup {name} Vip"
            ],
            "maxQuantity": 1
        },
        "Mvp": {
            "description": "This is the Mvp rank. Its features include...",
            "inherit_features": "Vip",
            "features": [
                "Check the \/realname of a player in \/nick quickly and easily!",
                "Check the players \/near you within a 200-block radius!"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/80C9A38A6BB0435FAE3D.png",
            "price": 10,
            "instantCommands": [
                "setgroup {name} Mvp"
            ],
            "maxQuantity": 1
        },
        "Ultra": {
            "description": "This is the Ultra rank. Its features include...",
            "inherit_features": "Mvp",
            "features": [
                "\/clearlagg – reduces lag by clearing mobs and items around the server!",
                "Ability to break bedrock with \/break!",
                "Access to \/backupinv – a backup inventory when your main inventory is full!"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/5305C9315733412DB997.png",
            "price": 20,
            "instantCommands": [
                "setgroup {name} Ultra"
            ],
            "maxQuantity": 1
        },
        "Legend": {
            "description": "This is the Legend rank. Its features include...",
            "inherit_features": "Ultra",
            "features": [
                "Set a custom \/nickname to use in the server – any colors allowed!",
                "Gain access to colorful \/chat! (Pick from 6 colors!)"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/26DBB86C5C584542980F.png",
            "price": 30,
            "instantCommands": [
                "setgroup {name} Legend"
            ],
            "maxQuantity": 1
        },
        "Ultimate": {
            "description": "This is the Ultimate rank. Its features include...",
            "inherit_features": "Legend",
            "features": [
                "\/enchant your item with any vanilla enchantments at an EXP cost!",
                "Purchase \/effects for EXP!",
                "Hide yourself from other players with \/vanish!"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/A9116E1CA4A349988178.png",
            "price": 40,
            "instantCommands": [
                "setgroup {name} Ultimate"
            ],
            "maxQuantity": 1
        },
        "Nightmare": {
            "description": "This is the Ultimate rank. Its features include...",
            "inherit_features": "Ultimate",
            "features": [
                "Rainbow chat included!",
                "Gives access to a third /pv!",
                "Increases prison earnings by 50%",
                "A free supreme gem will be issued at the beginning of each season."
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/imageedit19979545106.png",
            "price": 50,
            "instantCommands": [
                "setgroup {name} Nightmare"
            ],
            "maxQuantity": 1
        },
        "Universe": {
        	"description": "This is the Universe rank. Its features include...",
        	"inherit_features": "Nightmare",
        	"features": [
        	    "Gives access to a fourth /pv!",
        	    "Set nicknames of up to 24 characters!",
        	    "Increases prison earnings by 80%",
        	    "Access to all capes.",
        	    "Access to a personal mine."
        	],
        	"instantCommands": [
        	    "setgroup {name} Universe"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/1E1705EB4AA646FF9308.png",
        	"maxQuantity": 1,
        	"price": 75
        },
        "Vip -> Mvp": {
        	"description": "Upgrade your Vip rank to Mvp for lesser price.",
        	"inherit_features": "Mvp",
        	"instantCommands": [
        	   "setgroup {name} Mvp Vip"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/80C9A38A6BB0435FAE3D.png",
        	"price": 5
        },
        "Mvp -> Ultra": {
        	"description": "Upgrade your Mvp rank to Ultra for lesser price.",
        	"inherit_features": "Ultra",
        	"instantCommands": [
        	   "setgroup {name} Ultra Mvp"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/5305C9315733412DB997.png",
        	"price": 10 
        },
        "Ultra -> Legend": {
        	"description": "Upgrade your Ultra rank to Legend for lesser price.",
        	"inherit_features": "Ultimate",
        	"instantCommands": [
        	   "setgroup {name} Legend Ultra"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/26DBB86C5C584542980F.png",
        	"price": 10 
        },
        "Legend -> Ultimate": {
        	"description": "Upgrade your Legend rank to Ultimate for lesser price.",
        	"inherit_features": "Ultimate",
        	"instantCommands": [
        	   "setgroup {name} Ultimate Legend"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/A9116E1CA4A349988178.png",
        	"price": 10 
        },
        "Ultimate -> Nightmare": {
        	"description": "Upgrade your Ultimate rank to Nightmare for lesser price.",
        	"inherit_features": "Nightmare",
        	"instantCommands": [
        	   "setgroup {name} Nightmare Ultimate"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/imageedit19979545106.png",
        	"price": 10
        },
        "Nightmare -> Universe": {
        	"description": "Upgrade your Nightmare rank to Universe for lesser price.",
        	"inherit_features": "Universe",
        	"instantCommands": [
        	   "setgroup {name} Universe Nightmare"
        	],
        	"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/1E1705EB4AA646FF9308.png",
        	"price": 25
        }
    },
    "Gems": {
        "x5 Hestia Gem 💎": {
            "description": "This donation package gives 5 hestia gems!",
            "features": [
                "The hestia gem will give a kit with the enchants shown:"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/8497A9CBB6AF4D94B634.jpeg",
            "price": 6,
            "onlineCommands": [
                "give {name} hestia_gem 5"
            ],
            "slots": 1
        },
        "x5 Hephaestus Gem 💎": {
            "description": "This donation package gives you 5 hephaestus gems!",
            "features": [
                "The hephaestus gem will give a kit with the enchants shown:"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/5156E23377E540329342.jpeg",
            "price": 12,
            "onlineCommands": [
                "give {name} hephaestus_gem 5"
            ],
            "slots": 1
        },
        "x5 Tartarus Gem 💎": {
            "description": "This donation package gives you 5 tartarus gems",
            "features": [
                "The tartarus gem will give a kit with the enchants shown:"
            ],
            "image_url": "https:\/\/u.cubeupload.com\/kenygamer\/4AD5A25EE9B145FBB749.jpeg",
            "price": 18,
            "onlineCommands": [
                "give {name} tartarus_gem 5"
            ],
            "slots": 1
        },
        "x1 Supreme Gem 💎": {
    		"description": "The Supreme gem is much different than any other gem offered. Instead of a kit, the gem offers some of the most highly contested enchants on the server. They are obtainable only through the supreme gem.",
    		"features": [
    		    "Forcefield V - Enchant Book",
    		    "Lightning Strike IX - Enchant Book",
    		    "Enderman VI - Enchant Book",
    		    "Adhesive I - Enchant Book",
    		    "Demise V - Enchant Book",
    		    "Enlighted V - Enchant Book"
    		],
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/93C240600AE44797B346.jpeg",
    		"price": 15,
    		"onlineCommands": [
    		    "give {name} supreme_gem 1"
    		],
    		"slots": 1
    	}
    },
    "In-Game Money": {
    	"$$100,000,000 In-Game Money 💸": {
    		"description": "This donation package gives you $$100,000,000 to spend in-game.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/DAE9F9B1E7E747C79096.png",
    		"price": 5,
    		"instantCommands": [
    		    "givemoney {name} 100000000"
    		]
    	},
    	"$$400,000,000 In-Game Money 💸": {
    		"description": "Get $$800,000,000 for a better deal to spend in-game.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/8905AC45E09241518E0E.png",
    		"price": 10,
    		"instantCommands": [
    		    "givemoney {name} 400000000"
    		]
    	},
    	"$$800,000,000 In-Game Money 💸": {
    		"description": "This donation package gives you $$100,000,000 to spend in-game.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/8905AC45E09241518E0E.png",
    		"price": 15,
    		"instantCommands": [
    		    "givemoney {name} 800000000"
    		]
    	},
    	"$$1,600,000,000 In-Game Money 💸": {
    		"description": "Get $$1,600,000,000 for a great deal to spend on the server in-game.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/BB54DA0A7C5248438082.png",
    		"price": 20,
    		"instantCommands": [
    		    "givemoney {name} 1600000000"
    		]
    	},
    	"$$3,200,000,000 In-Game Money 💸": {
    		"description": "Get $$3,200,000,000 for an awesome deal to spend in-game.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/BB54DA0A7C5248438082.png",
    		"price": 30,
    		"instantCommands": [
    		    "givemoney {name} 3200000000"
    		]
    	},
    	"$$6,400,000,000 In-Game Money 💸": {
    		"description": "Get $$6400,000,000 for an incredible deal to spend in-game.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/D10563C5AC634A0889B7.png",
    		"price": 40,
    		"instantCommands": [
    		    "givemoney {name} 6400000000"
    		]
    	},
    	"$$10,000,000,000 In-Game Money 💸": {
    		"description": "Get the best deal for $$10,000,000,000 to spend in-game and enjoy the wealth!",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/81D426D0B42B4E61B931.png",
    		"price": 50,
    		"instantCommands": [
    		    "givemoney {name} 10000000000"
    		]
    	}
    },
    "Powerful Options": {
    	"Rainbow Chat 🌈": {
    		"description": "This donation package grants you permission to use rainbow /chat. Spice up that boring white chat!",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/4B75E9A87C6E422985BC.jpeg",
    		"price": 10,
    		"instantCommands": [
    		    "setuperm {name} core.chat.rainbow",
    		    "setuperm {name} core.command.chateffect"
    		],
    		"maxQuantity": 1
    	},
    	"Unban/pardon 🔑": {
    		"description": "If you were banned from the server, you can use this pardon package to get unbanned.",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/EBC6B39D12EC4509ACA9.png",
    		"price": 15,
    		"instantCommands": [
    		    "pardon {name}"
    		],
    		"maxQuantity": 1
    	}
    },
    "Limited Time Offers": {
    	"x15 Healing Cookies 🍪": {
    		"description": "With this donation package you get x15 Healing Cookies. Eating one of these cookies will give you 2 rows of extra health, and will last a full server restart. This is for PvPers!",
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/B38DD48F08714103942F.jpeg",
    		"price": 10,
    		"onlineCommands": [
    		    "give {name} healing_cookie 15"
    		],
    		"slots": 1
    	},
    	"May Lootbox 🎁": {
    		"description": "May's Lootbox is a very affordable bundle of gems, money, EXP and the recently added E-Tokens in store! Your luck is important in this money-saving bundle. Getting this lootbox will help you save $26 to up to $42 instead of buying the separate packages for each.",
    		"features": [
    		    "50-250 E-Tokens",
    		    "1-3 Tartarus Gems",
    		    "10 Atlas Gems",
    		    "3-5 Hephaestus Gems",
    		    "$500,000,000-$1,000,000,000 In-Game Money",
    		    "1,000K-2,500K EXP"
    		],
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/CBF68FCA1EBA486EB245.png",
    		"price": 30,
    		"onlineCommands": [
    		    "give {name} monthly_lootbox 1"
    		],
    		"slots": 4
    	},
    	"Pet 🐾": {
    		"description": "Ever wanted to have a pet? Well now you can! For just $15 you will get a shield and faithful companion in your gameplay. They are immortal and will always stick by your side. They can do crazy things if they see you in danger!",
    		"image_url": "https:\/\/gamepedia.cursecdn.com\/minecraft_gamepedia\/e\/e0\/Tamed_Wolf_with_Red_Collar.png?version=7639c69991752a0f42c5b0f28f3813dd",
    		"price": 15,
    		"onlineCommands": [
    		    "spawnpet wolf \"\\{name}'s Pet\" 1 true {name}"
    		],
    		"maxQuantity": 1
    	},
    	"Wings 🦋": {
    		"description": "Take flight with our newest cosmetic item, Wings! Customize this advanced item however you like to make it your own. Fly past the competition in style! This is a non pay2win item.",
    		"price": 15,
    		"onlineCommands": [
    		    "setuperm {name} eliteextras.wings"
    		],
    		"image_url": "https:\/\/u.cubeupload.com\/kenygamer\/A51056A5DC6646F98543.png",
    		"maxQuantity": 1
    	}
    }
}