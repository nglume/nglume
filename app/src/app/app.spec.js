describe('Bootstrap', function () {
    describe('isCurrentUrl', function () {
        var AppCtrl, $location, $scope;

        beforeEach(angular.mock.module('app'));

        beforeEach(inject(function ($controller, _$location_, $rootScope) {
            $location = _$location_;
            $scope = $rootScope.$new();
            AppCtrl = $controller('app.controller', {$location: $location, $scope: $scope});
        }));

        it('should pass a dummy test', inject(function () {
            expect(AppCtrl).toBeTruthy();
        }));
    });
});