#!/bin/bash

# Cambiar contraseña de usuario MySQL

NEW_PASSWORD="IvnubiohOtm9VLIAu7q2Pp5PvDikKV2s1glsQl1CU4U="

# Se asume que mysql command está disponible sin contraseña (socket unix)
sudo mysql -u root <<EOF
ALTER USER 'offside'@'localhost' IDENTIFIED BY '$NEW_PASSWORD';
ALTER USER 'offside'@'%' IDENTIFIED BY '$NEW_PASSWORD';
FLUSH PRIVILEGES;
SELECT "✅ Database password updated successfully";
EOF
