@Library(['dockerHelpers', 'testing', 'deploy']) _

pipeline {
	agent any

	environment {
		// Workspace directory
		WORKSPACE = sh(script: 'pwd', returnStdout: true).trim()

		// Docker repositories
		TEST_REGISTRY = 'rg.fr-par.scw.cloud/testing-images'
		REGISTRY = 'rg.fr-par.scw.cloud/app-images'
		REGISTRY_HOST = 'rg.fr-par.scw.cloud'

		// Images
		PHP_TEST_IMAGE = 'php:8.5'
		NODE_TEST_IMAGE = 'node:lts'
		APP_IMAGE = 'cook-book-shopping-list'

		DEPLOY_STACK_NAME = 'cook-book'
	}

	stages {
		stage('Docker Login') {
			steps {
				script {
					withCredentials([string(credentialsId: 'scaleway_secret_key', variable: 'SECRET')]) {
						dockerRegistryLogin(
							registryUrl: TEST_REGISTRY,
							password: env.SECRET
						)
					}
				}
			}
		}

		stage('Pull testing images') {
			steps {
				script {
					sh """
						docker pull ${TEST_REGISTRY}/${PHP_TEST_IMAGE} || true
						docker pull ${TEST_REGISTRY}/${NODE_TEST_IMAGE} || true
				 """
				}
			}
		}

		stage('Testing') {
			stages {
				stage('Prepare') {
					steps {
						script {
							runCommandInTest(
								image: "${TEST_REGISTRY}/${PHP_TEST_IMAGE}",
								command: 'composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader'
							)
							// Prepare route definitions for testing
							runCommandInTest(
								image: "${TEST_REGISTRY}/${PHP_TEST_IMAGE}",
								command: 'cp .env.example .env && php artisan key:generate --ansi && php artisan config:clear && php artisan wayfinder:generate --with-form --no-interaction',
							)
							// Build frontend assets for tests that render the application shell
							runCommandInTest(
								image: "${TEST_REGISTRY}/${NODE_TEST_IMAGE}",
								command: 'pnpm install --frozen-lockfile && pnpm build',
							)
						}
					}
				}
				stage('Run Tests'){
					parallel{
						stage('PHP Tests'){
							steps {
								script {
									fullPhpTest(
										image: "${TEST_REGISTRY}/${PHP_TEST_IMAGE}",
										runInstall: false,
										phpstanCommand: './vendor/bin/phpstan analyse --memory-limit=1G',
										csCommand: './vendor/bin/pint --test',
										testOptions: '-e APP_ENV=testing -e APP_DISCOVERY_CACHE=false -e DB_CONNECTION=sqlite -e DB_DATABASE=:memory:',
										testCommand: 'cp .env.example .env && php artisan key:generate --ansi && php artisan config:clear && php artisan test --parallel'
									)
								}
							}
						}
						stage('JS tests') {
							steps {
								script {
									// Run Vitest tests
									fullNodeTest(
										image: "${TEST_REGISTRY}/${NODE_TEST_IMAGE}",
										typecheck: true,
										test: true,
										testCommand: 'LARAVEL_BYPASS_ENV_CHECK=1 pnpm test:node --maxWorkers=1 --testTimeout=10000'
									)
								}
							}
						}
						stage('Test Docker Build') {
							when {
								changeRequest()
							}
							steps {
								script {
									sh """
										docker build \
											--file docker/production/Dockerfile \
											--tag ${APP_IMAGE}:test-build-${env.BUILD_NUMBER} \
											--no-cache \
											.
										docker rmi ${APP_IMAGE}:test-build-${env.BUILD_NUMBER} || true
									"""
								}
							}
						}
					}
				}
			}
		}

		stage('Deploy production') {
			when {
				anyOf {
					branch 'main'
					branch 'master'
				}
			}

			stages {
				stage('Build and push image') {
					steps {
						withCredentials([string(credentialsId: 'scaleway_secret_key', variable: 'SECRET')]) {
							script {
								def buildTags = ["latest"]

								// If the build is from a tagged commit, also tag the image with the tag
								def gitTags = sh(returnStdout: true, script: "git tag --points-at HEAD").trim().split("\\n")
								if (gitTags.size() > 0 && gitTags[0] != '') {
									for (tag in gitTags) {
										buildTags.add("${tag}")
									}
								}

								echo 'Building multi-platform production Docker image...'
								dockerBuildMultiArch(
									registry: env.REGISTRY,
									registryHost: env.REGISTRY_HOST,
									registryPassword: env.SECRET,
									image: env.APP_IMAGE,
									contextDir: '.',
									tags: buildTags,
									dockerfile: 'docker/production/Dockerfile',
								)
							}
						}
					}
				}

				stage('Deploy') {
					steps {
						script {
							echo 'Deploying the new Docker image to the server...'
						}
						// load komodo_deploy_api_key and komodo_deploy_api_secret
						withCredentials([
							string(credentialsId: 'komodo_deploy_api_key', variable: 'API_KEY'),
							string(credentialsId: 'komodo_deploy_api_secret', variable: 'API_SECRET')
						]) {
							script {
								deployKomodoStack(
									apiKey: env.API_KEY,
									apiSecret: env.API_SECRET,
									deployStackName: env.DEPLOY_STACK_NAME
								)
							}
						}
						script {
							echo 'Deployment completed successfully.'
						}
					}
				}
			}
		}
	}

	post {
		always {
			sh 'sudo /usr/local/sbin/jenkins-clean-workspace-perms "$WORKSPACE" || true'
			cleanWs()
		}
		success {
			echo 'Pipeline completed successfully!'
		}
		failure {
			echo 'Pipeline failed!'
		}
	}
}
