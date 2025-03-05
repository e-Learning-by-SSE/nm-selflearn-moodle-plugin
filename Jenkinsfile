pipeline {
    agent { 
        label 'docker'
    }
    environment {
        PLUGIN_DIR = 'selflearn'
        DOCKER_BUILD_IMAGE = 'alpine:latest'
        ARTIFACT_NAME = 'selflearn-moodle-plugin.zip'
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
            steps {
                sh "apk add --no-cache zip"
            }
        }
        
        stage('Package Plugin') {
            steps {
                sh "zip -r ${ARTIFACT_NAME} ${env.WORKSPACE}/selflearn"
            }
        }
        
        stage('Archive Artifact') {
            steps {
                archiveArtifacts artifacts: "${ARTIFACT_NAME}", fingerprint: true
            }
        }
    }
}

