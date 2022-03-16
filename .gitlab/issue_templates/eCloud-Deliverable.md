<!-- Title: eCloud-Deliverable.md -->

### User Story
<!-- A sentence or two that outlines the desired outcome written from the perspective of the end user or customer -->

<!-- gui example -->
End User - View a list of my VPCs so that I can manage my resources   

<!-- api example -->
API User - Retrieve a vpc collection so that I can perform actions on my resources   


### Acceptance Criteria
<!-- The conditions/requirements that must be met to be accepted -->

<!-- gui example -->
* [ ] GIVEN I am on the VPC collection page | WHEN the page loads | THEN I see a list of my VPCs showing ID, Name, status, etc   
* [ ] GIVEN I am on the VPC item page | WHEN the name is updated | THEN a request is made to the api AND I see a confirmation message

<!-- api example -->
* [ ] GIVEN I request the collection/item | WHEN the request completes | THEN I can see the id, name, status, etc
* [ ] GIVEN I create an item | WHEN the correct properties are provided | THEN an accepted status is returned


<!-- ENFORCEMENT-END -->

<!--- Set Team label - Delete as appropriate -->
/label ~PHP ~DevOps 

<!--- set product or project labels - If appropriate  -->
/label ~eCloud ~"eCloud VPC"

<!--- set product or project milestone - If appropriate  -->
/milestone %

<!--- set initial issue status, risk, priority, weight & estimate - see handbook if unsure  -->
/label ~"To Do" ~P2 ~"risk::low" 
/weight 
/estimate 
