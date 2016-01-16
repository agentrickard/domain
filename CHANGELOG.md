Changelog
=====

The 8.x-1.x version is a ground up rewrite of a module originally written for
Drupal 5.

The following feature sets are considered critical for each release stage. Items
marked with [x] are considered complete.

# Alpha
- [x] Domain entity for storage and configuration
- [x] Domain negotiation for active requests
- [x] API for modifying active domain requests
- [x] API for modifying paths per domain
- [x] Mechanism for access restriction inactive domains
- [x] Form for adding / editing / deleting domain records
- [x] Validation of domain record patterns
- [x] Domain server information block
- [x] Domain switcher block
- [x] Domain context for block visibility
- [x] Entity reference support, with an API for altering entity lists
- [x] Alias registration and negotiation
- [x] Alias validation
- [x] Configuration overrides per domain
- [x] Language-aware configuration overrides
- [x] Enable node access control via domain references (Domain Access)
- [x] Allow nodes to be assigned to domains
- [x] Allow users to be assigned as domain editors
- [x] Set permissions for content editing based on domain
- [x] Restrict publication options based on user domains
- [x] Support the all affiliates concept of publication
- [x] Support the all affiliates concept for editors
- [x] Test and document cross-domain logins
- [ ] Provide common views support for attached fields
- [ ] Recreate the Domain Content module with bulk operations

# Beta
- [x] Actions for domain operations
- [x] Drush support for domain operations
- [ ] Make language optional for domain overrides
- [ ] Provide a user interface for domain overrides
- [ ] Recreate the Domain Theme module
- [ ] Advanced drush integration / complete labelled tasks
- [ ] Check domain responses on configuration forms
- [ ] Handle site name overrides -- perhaps as a new field?
- [ ] Restore the domain_grant_all permission?
- [ ] Domain token support
- [ ] Test cron handling
- [ ] Module configurations
  - [ ] Allow configuration of access-exempt paths for inactive domains
  - [ ] www prefix handling
  - [ ] Add domain-specific CSS classes
  - [ ] Path matching for URL rewrites?
  - [ ] Allow non-ascii characters in domains
- [ ] Recreate the Domain Nav module

# Final
- [ ] Security review
- [ ] Provide an upgrade path from 6.x
- [ ] Provide an upgrade path from 7.x-3.x

