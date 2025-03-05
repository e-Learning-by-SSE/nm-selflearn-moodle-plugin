pipeline {
    agent { 
        docker {
            image "${DOCKER_BUILD_IMAGE}"
            reuseNode true // This is important to enable the use of the docker socket for sidecar pattern later
         }
    }
    environment {
        PLUGIN_DIR = 'selflearn'
        DOCKER_BUILD_IMAGE = 'joshkeegan/zip:latest'
        ARTIFACT_NAME = 'selflearn-moodle-plugin.zip'
    }
    options {
        ansiColor('xterm')
    }

    stages {        
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

