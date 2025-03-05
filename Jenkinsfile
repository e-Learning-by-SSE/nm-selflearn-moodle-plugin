pipeline {
    agent { 
        label 'docker'
    }
    environment {
        PLUGIN_DIR = 'selflearn'
        ARTIFACT_NAME = 'moodle-plugin.zip'
        DOCKER_BUILD_IMAGE = 'moodlehq/moodle-php-apache:8.2'
        MOODLE_DOWNLOAD_URL = 'https://download.moodle.org/download.php/direct/stable405/moodle-latest-405.tgz'
    }
    options {
        ansiColor('xterm')
    }

    stages {
        stage('Build Container') {
            agent {
                docker {
                    image "${DOCKER_BUILD_IMAGE}"
                    reuseNode true // This is important to enable the use of the docker socket for sidecar pattern later
                }
            }
            environment {
                NPM_TOKEN = credentials('GitHub-NPM')
            }
            steps {
                sh "wget -O moodle.tgz ${MOODLE_DOWNLOAD_URL}"
                sh 'tar -xzf moodle.tgz -C /var/www/html'
                sh 'rm moodle.tgz'
            }
        }
        
        stage('Package Plugin') {
            steps {
                sh "zip -r ${ARTIFACT_NAME} ${PLUGIN_DIR}"
            }
        }
        
        stage('Archive Artifact') {
            steps {
                archiveArtifacts artifacts: "${ARTIFACT_NAME}", fingerprint: true
            }
        }
    }
}

