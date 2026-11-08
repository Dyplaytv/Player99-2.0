import os
import glob

# Files to update for the Cadastre-se Aqui button
nav_files = [
    "afiliados.php",
    "cadastro.php",
    "index.php",
    "login_cliente.php",
    "loja.php",
    "parceiros.php"
]

nav_button = '            <a href="cadastro.php" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #4721fb); box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4); border: 1px solid rgba(255,255,255,0.2);">Cadastre-se Aqui</a>'

for file in nav_files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # Insert button at the end of nav-links
        if '<div class="nav-links">' in content and 'Cadastre-se Aqui' not in content:
            content = content.replace(
                '<div class="nav-links">',
                f'<div class="nav-links">\n{nav_button}'
            )
            with open(file, 'w', encoding='utf-8') as f:
                f.write(content)
        print(f"Updated nav in {file}")

# Files to update for landing-img css
landing_files = ["afiliados.php", "index.php", "parceiros.php"]
old_css = """        .landing-img {
            width: 100%;
            height: auto;
            display: block; /* remove espaçamento indesejado abaixo da imagem */
        }
        a {
            display: block;
            text-decoration: none;
        }"""
new_css = """        .landing-img {
            width: 100%;
            max-width: 900px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        a:first-of-type .landing-img {
            margin-top: 100px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        a:last-of-type .landing-img {
            margin-bottom: 50px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        }
        a {
            display: block;
            text-decoration: none;
        }"""

for file in landing_files:
    if os.path.exists(file):
        with open(file, 'r', encoding='utf-8') as f:
            content = f.read()
            
        if old_css in content:
            content = content.replace(old_css, new_css)
            with open(file, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Updated CSS in {file}")
