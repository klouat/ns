import json

# Load JSON file
with open("listskill.json", "r", encoding="utf-8") as file:
    skills = json.load(file)

# Ask user for type
user_type = input("Enter skill type: ")

# Print only the skill ID
for skill in skills:
    if str(skill.get("type")) == user_type:
        print(skill.get("id"))
