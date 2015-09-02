(() => {

    let seededChance = new Chance(1);

    describe('Image Model', () => {

        let tagData = {
            imageId: seededChance.guid(),
            publicId : seededChance.string({length: 20}),
            version : Math.floor(chance.date().getTime() / 1000),
            folder : seededChance.word(),
            format : seededChance.pick(['gif', 'jpg', 'png']),
            alt : seededChance.sentence(),
            title : chance.weighted([null, seededChance.sentence()], [1, 2]),
        };

        it('should instantiate a new image', () => {

            let image = new common.models.Image(tagData);

            expect(image).to.be.instanceOf(common.models.Image);

        });

    });

})();