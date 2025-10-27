pipeline {
  agent any

  options {
    timestamps()
    ansiColor('xterm')
  }

  environment {
    // Carpeta del host montada en el contenedor php-apache
    WEB_ROOT = '/volume1/docker/web/code'
  }

  triggers {
    githubPush()
  }

  stages {
    stage('Checkout') {
      steps {
        git branch: 'main', url: 'https://github.com/royoruiz/pruebas.git'
      }
    }

    stage('Sync to webroot') {
      steps {
        sh '''
          set -euxo pipefail
          mkdir -p "$WEB_ROOT"
          rsync -a --delete \
            --exclude '.git/' \
            --exclude 'Jenkinsfile' \
            --exclude 'Dockerfile' \
            --exclude 'Dockerfile.php' \
            --exclude 'compose.yaml' \
            ./ "$WEB_ROOT"/
        '''
      }
    }
  }

  post {
    success { echo '✅ Despliegue completado: archivos actualizados en el docroot.' }
    failure { echo '❌ Falló el despliegue.' }
  }
}
