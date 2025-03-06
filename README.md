# Grupo1

## Despliegue en Azure con Cloud-Init

Este proyecto puede ser desplegado automáticamente en Azure usando cloud-init. Sigue estos pasos:

### 1. Preparación

1. Crea un archivo `cloud-init.yml` con el siguiente contenido para la configuracion de las maquinas virtuales que conforman el balanceador de carga:


```yaml
#cloud-config
package_upgrade: true

packages:
  - apt-transport-https
  - ca-certificates
  - curl
  - software-properties-common
  - git
  - docker.io
  - docker-compose

write_files:
  # Crear archivo .env en la carpeta api
  - path: /app/api/.env
    permissions: '0600'
    content: |
      # Database Configuration (Conexión a DB externa)
      DB_HOST=your_external_db_host
      DB_NAME=susurros_db
      DB_USER=your_db_user
      DB_PASS=your_db_password
      DB_PORT=5432

      # JWT Configuration
      JWT_SECRET=your_very_secure_jwt_secret_here
      JWT_EXPIRATION=3600

      # API Configuration
      RATE_LIMIT=100
      RATE_LIMIT_TIME=3600

      # Docker Specific
      APP_ENV=production
      APP_DEBUG=false

runcmd:
  # Actualizar repositorios
  - sudo apt-get update

  # Iniciar Docker
  - sudo systemctl start docker
  - sudo systemctl enable docker

  # Instalar Docker Compose
  - sudo curl -L "https://github.com/docker/compose/releases/download/v2.24.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
  - sudo chmod +x /usr/local/bin/docker-compose

  # Crear directorio para la aplicación
  - mkdir -p /app

  # Clonar el repositorio
  - git clone https://github.com/AlexanderM2004/Grupo1_Proyecto_Apl_Tec_Web.git /app

  # Configurar permisos
  - chown -R www-data:www-data /app
  - chmod -R 755 /app
  - chmod 600 /app/api/.env

  # Configurar variables de entorno de PHP
  - echo "php_value upload_max_filesize 64M" >> /app/api/.htaccess
  - echo "php_value post_max_size 64M" >> /app/api/.htaccess
  - echo "php_value max_execution_time 300" >> /app/api/.htaccess
  - echo "php_value max_input_time 300" >> /app/api/.htaccess

  # Construir y levantar contenedores Docker
  - cd /app
  - docker-compose build
  - docker-compose up -d

  # Configurar firewall
  - sudo ufw allow 80/tcp
  - sudo ufw allow 443/tcp
  - sudo ufw allow 22/tcp
  - sudo ufw --force enable

  # Configurar seguridad adicional
  - apt-get install -y fail2ban
  - systemctl enable fail2ban
  - systemctl start fail2ban
```

### 2. Despliegue

#### Usando Azure CLI

```bash
az vm create \
  --resource-group tuGrupoDeRecursos \
  --name tuNombreVM \
  --image Ubuntu2204 \
  --custom-data cloud-init.yml \
  --generate-ssh-keys \
  --size Standard_B2s
```

#### Usando Portal de Azure

1. Ve al Portal de Azure
2. Crea una nueva Máquina Virtual
3. En la sección "Advanced"
4. Pega el contenido del `cloud-init.yml` en el campo "Custom data"

### 3. Post-Despliegue

1. Conectarse a la VM:
```bash
ssh azureuser@tu-ip-publica
```

2. Verificar la instalación:
```bash
sudo tail -f /var/log/cloud-init-output.log
```

3. Verificar que Docker está funcionando:
```bash
cd /app
docker-compose ps
docker-compose logs
```

### 4. Configuración Importante

Antes del despliegue, asegúrate de:

1. Modificar las variables en el archivo cloud-init.yml:
   - `DB_HOST`: Host de tu base de datos externa
   - `DB_USER`: Usuario de la base de datos
   - `DB_PASS`: Contraseña de la base de datos
   - `JWT_SECRET`: Una clave secreta segura para JWT

2. Configurar el Network Security Group (NSG) en Azure para permitir tráfico en los puertos:
   - 80 (HTTP)
   - 443 (HTTPS)
   - 22 (SSH)

3. Verificar que tu servidor de base de datos permite conexiones desde la IP de la VM de Azure

### 5. Mantenimiento

Para reiniciar los servicios:
```bash
cd /app
docker-compose restart
```

Para ver logs:
```bash
docker-compose logs -f
```

Para actualizar el código:
```bash
cd /app
git pull
docker-compose up -d --build
```