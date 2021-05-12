# Commerce Data Source for Sprout Reports Changelog

## 1.0.5 - 2021-05-11

### Fixed

- Fixed bug when running Order History report with calculate totals setting enabled ([#8])
- Updated Order History and Product Revenue Data Sources to exclude deleted Elements ([#9])
  
[#8]: https://github.com/barrelstrength/craft-sprout-reports-commerce/issues/8
[#9]: https://github.com/barrelstrength/craft-sprout-reports-commerce/issues/9

## 1.0.4 - 2020-03-14

### Changed
- Updated `barrelstrength/sprout-reports` requirement to v1.3.3

### Fixed
- Fixed asset base path to be compatible with updates in `barrelstrength/sprout-base-reports`
- Fixed date range output to be compatible with updates in `barrelstrength/sprout-base-reports`
- Fixed bug where `dataSourceBaseUrl` was not defined after editing a report with validation errors

## 1.0.1 - 2019-07-09

### Changed
- Updated barrelstrength/sprout-base-reports requirement to 1.3.1

### Fixed
- Fixed bug with Report model instantiation causing CSV downloads to fail

## 1.0.0 - 2019-07-08

### Added
- Initial Craft 3 release

