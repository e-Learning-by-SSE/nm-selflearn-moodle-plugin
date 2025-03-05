pipeline {
    agent { 
        label 'docker'
    }
    environment {
        PLUGIN_DIR = 'selflearn'
        BUILD_IMAGE = 'joshkeegan/zip:latest'
        ARTIFACT_NAME = 'selflearn-moodle-plugin.zip'
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
                sh "cd ${env.WORKSPACE} && zip -r ${ARTIFACT_NAME} selflearn"
            }
        }
        
        stage('Archive Artifact') {
            steps {
                archiveArtifacts artifacts: "${ARTIFACT_NAME}", fingerprint: true
            }
        }
    }
}
