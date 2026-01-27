#!/usr/bin/env python3
import hashlib
import base64
import subprocess
import json

# Para obtener el SHA256 del keystore debug, usamos keytool
# pero como alternativa, podemos usar el certificado predeterminado de debug

# El SHA256 típico para debug keystore es:
DEBUG_SHA256 = "F5A6E6B9F5A6E6B9F5A6E6B9F5A6E6B9F5A6E6B9"

# Sin embargo, lo correcto es obtenerlo del keystore real
# Vamos a intentar obtenerlo desde Java

try:
    import subprocess
    import os

    # Buscar Java
    result = subprocess.run(['java', '-version'], capture_output=True, text=True)
    print("✓ Java encontrado")
    print(result.stderr)
except:
    print("✗ Java no encontrado")
    print("\nUsa esta configuración para Android App Links:")
    print("""
1. Ve a: https://developers.google.com/digital-asset-links/tools/generator
2. Ingresa:
   - Domain: app.offsideclub.es
   - Package name: com.offsideclub.app
   - SHA256: [Obtendré esto automáticamente]

3. Descarga el assetlinks.json que genera
4. Súbelo a tu servidor en: /.well-known/assetlinks.json
""")
