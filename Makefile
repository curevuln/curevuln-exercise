dirs := content-security-policy nginx-http-header-injection php-access php-csrf php-docroot php-dom-based-xss php-markdown-xss php-os-command-injection php-reflected-xss php-reflected-xss-form php-sessionFixation php-sql-injection php-ssrf php-stored-xss php-upload-file-rce rails-javascript-scheme-xss rails-ssrf server-side-template-injection-smarty vuejs-template-injection-on-php

build:
	@for dir in $(dirs); do \
		echo "Building container for $$dir"; \
		if [ -d $$dir ]; then \
			cd $$dir; \
			docker compose build; \
			cd -; \
		fi \
	done
