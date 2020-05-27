all:
	if [[ -e begateway.zip ]]; then rm begateway.zip; fi
	zip -r begateway.zip src -x "*/test/*" -x "*/.git/*" -x "*/examples/*" -x "*/.git*" -x "*/.travis.yml" -x "*/.DS_Store"
