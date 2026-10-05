# Rankboard
Run from the project root:  php -S localhost:8000
Open: http://localhost:8000/frontend/
Make an admin: register, then edit "role" to "admin" in backend/data/players.json (or your DB workbench).

# Prompt
You are a second year student in a web dev school, and you have an assignement for the end of the second sprint of the year, the assignement is to create a website using html, tailwind, js, and php, use  fetch and json php functions to store the data in 4 json files, use object oriented programming and classes for every function possible, and keep the css good looking but not too complexe, the main idea of the project is a board game platform
the Json files that are gonna be used for the project include:
Player: used for registering and loging into the site, includes the rank of the player
Game: used to store the games that the players can play
Game session: used to record each game session that was completed either with a win or a loss 
Game category: used to sort through the games
the games that the player can play are 2 small js games and results are saved in the Json files.
adding new data to the json files is done by sending a javascript HTTP post request to php and then appended through php, also reading the files is also through php with header('Content-Type:...) and then fetched by javascript
all display is done with javascript and the index file is html not php, html and javascript files should be in a frontend folder and php files and the data folder should be in a backend folder
every session is a single player session of the selected game with either win or loss
the rank system is point based each loss takes away one point and each win gives 2 points
0p = iron rank
5p = copper rank
10p = bronze rank
20p = gold rank
35p = diamond rank
50p = beast rank
60p+ = immortal rank
the site should have a player and admin roles that can be assigned in the database workbench
responsive dark and clean themed tailwind for the styling