IMAGE := php86
TTY := $(shell [ -t 0 ] && echo -t)
STAMP := .build

CURRENT ?= idea
REQUESTED ?= draft
ROLE ?= editor
DEBUG ?=

.PHONY: build run shell lint clean

build: $(STAMP)

$(STAMP): Dockerfile
	docker build -t $(IMAGE) .
	@touch $(STAMP)

run: $(STAMP)
	docker run --rm -i $(TTY) -e DEBUG=$(DEBUG) -v $(CURDIR):/app $(IMAGE) php podcast-episode.php $(CURRENT) $(REQUESTED) $(ROLE)

lint: $(STAMP)
	docker run --rm -i $(TTY) -v $(CURDIR):/app $(IMAGE) php -l podcast-episode.php

shell: $(STAMP)
	docker run --rm -i $(TTY) -v $(CURDIR):/app $(IMAGE) bash

clean:
	rm -f $(STAMP)
