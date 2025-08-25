pipeline {
    agent { 
        label 'docker'
    }
    environment {
        BUILD_IMAGE = 'joshkeegan/zip:latest'
        TIMESTAMP = sh(script: 'date +"%Y-%m-%d"', returnStdout: true).trim()
        ARTIFACT_NAME = "selflearn-moodle-plugin-${TIMESTAMP}.zip"
    }
    options {
        ansiColor('xterm')
    }

    stages {        
        stage('Package Plugin') {
            agent {
                docker {
                    image "${BUILD_IMAGE}" 
                    reuseNode true
                }
            }
            steps {
                sh "cd ${env.WORKSPACE} && zip -r ${ARTIFACT_NAME} . -x 'Jenkinsfile'"
            }
        }
        
        stage('Archive Artifact') {
            steps {
                archiveArtifacts artifacts: "${ARTIFACT_NAME}", fingerprint: true
            }
        }
    }
}
