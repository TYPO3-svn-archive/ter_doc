plugin.tx_terdoc {
		settings {
			# Full path to the repository: Enter the full path to the local extension repository. Example: /var/www/fileadmin/ter/
			repositoryDir = /var/www/fileadmin/ter/

			# Commandline for unzip application: Enter the full command line for your unzip application,
			# using the placeholders ###ARCHIVENAME### and ###DIRECTORY###.
			# (Note: Use forward slashes '/' for windows paths!)
			unzipCommand = unzip -qq ###ARCHIVENAME### -d ###DIRECTORY###

			# Check if the renderer should output what it's currently doing (for debugging)
			cliVerbose = 0

			# If you specify a path and file name here, messages will logged to this file (if verbosity is checked). Otherwise messages are sent directly to STDOUT
			logFullPath =

			# If you specify a path and file name here, messages will logged to this file (if verbosity is checked). Otherwise messages are sent directly to STDOUT
			homeDir = typo3temp/tx_terdoc/

			# Tells what Docbook version to generate
			docbook_version = 4,5

			# Tells what Docbook version is the default one
			docbook_version_default = 4
		}
}
