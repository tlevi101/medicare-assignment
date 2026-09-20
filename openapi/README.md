# OpenAPI specification

The specification is split across several files so that paths and components are easily discoverable and maintainable. 
The main entry point is `openapi.yaml`, which references the other files.

```
openapi.yaml              entry point: info, servers, tags, security, path refs
paths/<resource>/         one file per path: index.yaml for the collection,
                          <singular>.yaml for the item path
components/schemas/       request and response models
components/responses/     reusable error responses
components/parameters/    reusable query and path parameters
components/securitySchemes/
```

## Working on it

```bash
npm run api:lint       # validate the spec and the house rules in redocly.yaml
npm run api:bundle     # resolve every $ref into public/docs/openapi.yaml
npm run api:preview    # serve interactive docs on http://localhost:8080
```
