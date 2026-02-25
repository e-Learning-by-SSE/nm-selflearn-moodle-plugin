@Library('web-service-helper-lib') _

pipeline {
    agent { 
        label 'docker'
    }
    environment {
		MOODLE_IMAGE = 'ghcr.io/e-learning-by-sse/moodle-testing:php8.3-v5.1.3'
		BUILD_IMAGE = 'joshkeegan/zip:latest'
        TIMESTAMP = sh(script: 'date +"%Y-%m-%d"', returnStdout: true).trim()
        ARTIFACT_NAME = "selflearn-moodle-plugin-${TIMESTAMP}.zip"
		DOCKER_ARGS = "-i -v /var/run/docker.sock:/var/run/docker.sock -v ${env.WORKSPACE}:/var/www/html/public/mod/selflearn -u 0:0 --entrypoint=''"
    }
    options {
        ansiColor('xterm')
		skipDefaultCheckout(true)
    }

    stages {        
        stage('Prepare Workspace') {
			steps {
				cleanWs()
				checkout scm
			}
		}
		stage('Test') {
            environment {
				POSTGRES_DB = 'MoodleDb'
                POSTGRES_USER = 'username'
                POSTGRES_PASSWORD = 'password'
				DB_DOCKER_ARGS = ""
            }
			agent {
                docker {
                    image "${MOODLE_IMAGE}"
                    reuseNode true // This is important to enable the use of the docker socket for sidecar pattern later
					args "${DOCKER_ARGS} "
                }
            }
			steps {
				script {
					withPostgres([dbUser: env.POSTGRES_USER, dbPassword: env.POSTGRES_PASSWORD, dbName: env.POSTGRES_DB])
                        .insideSidecar("${MOODLE_IMAGE}", "${DOCKER_ARGS}") {
						sh '''#!/usr/bin/env bash
          set -euxo pipefail

          # Where Moodle lives in your image
          MOODLE_DIR=/var/www/html/

          # Reports + dataroot (must be writable)
          mkdir -p "$WORKSPACE/build/test-results"
          mkdir -p "$WORKSPACE/moodledata"
		  mkdir -p "$WORKSPACE/moodledata_phpunit"
		  rm -rf /moodledata_phpunit || true
		  ln -s "$WORKSPACE/moodledata_phpunit" /moodledata_phpunit

          # 2) Create config.php for CI using Postgres sidecar "db"
          cat > "$MOODLE_DIR/config.php" <<'PHP'
<?php
unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'db';
$CFG->dbname    = getenv('POSTGRES_DB') ?: 'MoodleDb';
$CFG->dbuser    = getenv('POSTGRES_USER') ?: 'username';
$CFG->dbpass    = getenv('POSTGRES_PASSWORD') ?: 'password';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = [
  'dbpersist' => 0,
  'dbport' => 5432,
  'dbsocket' => '',
];

$CFG->wwwroot   = 'http://localhost';
$CFG->dataroot  = '/moodledata';
$CFG->directorypermissions = 02777;
$CFG->phpunit_dataroot = '/moodledata_phpunit';
$CFG->phpunit_prefix   = 'phpu_';

require_once(__DIR__ . '/lib/setup.php');
PHP

          # Make dataroot available at /moodledata
          # (Bind-mount may not exist in this helper; use a symlink fallback)
          rm -rf /moodledata || true
          ln -s "$WORKSPACE/moodledata" /moodledata

          # 3) Run PHPUnit init + tests + JUnit report
          cd "$MOODLE_DIR"

          php public/admin/tool/phpunit/cli/init.php
        '''
		  
						catchError(buildResult: 'SUCCESS', stageResult: 'FAILURE') {		  
							php -d pcov.enabled=1 -d pcov.directory=/var/www/html/public/mod/selflearn vendor/bin/phpunit --testsuite mod_selflearn --log-junit "$WORKSPACE/build/test-results/junit.xml" --coverage-clover "$WORKSPACE/build/coverage/clover.xml" --coverage-filter /var/www/html/public/mod/selflearn
						}
                    }
				}
			}
			post {
			    always {
					junit testResults: 'build/test-results/*.xml', allowEmptyResults: true
					archiveArtifacts artifacts: 'build/test-results/*.xml', fingerprint: true, allowEmptyArchive: true
					
					if (fileExists('build/coverage/clover.xml')) {
						catchError(buildResult: 'SUCCESS') {
						    recordCoverage(
								tools: [[parser: clover]],
								id: 'clover', name: 'Clover Coverage',
								sourceCodeRetention: 'EVERY_BUILD',
								skipPublishingChecks: true
						    )
						}
					}
				}
			}
        }
        stage('Package Plugin') {
            agent {
                docker {
                    image "${BUILD_IMAGE}" 
                    reuseNode true
                }
            }
            steps {
                sh "cd ${env.WORKSPACE} && zip -r ${ARTIFACT_NAME} . -x 'Jenkinsfile' '.git/*' '.git'"
            }
        }
        
        stage('Archive Artifact') {
            steps {
                archiveArtifacts artifacts: "${ARTIFACT_NAME}", fingerprint: true
            }
        }
    }
}