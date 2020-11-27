<!-- Title: UserStory-Deliverable.md -->
<!--- THIS TEMPLATE IS TO BE USED FOR NEW FEATURES OR CHANGE REQUESTS -->

### What is the Feature/Change?
<!-- Enter clear and concise description of what your feature or change request is. -->



### Assumptions:
1. All success and error events will be logged in the existing logging framework
2. All success and error events will update the status of the resource in APIO


### Prerequisites:
<!-- Link to any issues/etc that are required for development to begin -->
- none


### User Stories

| As an <type of user> | I want to <perform some task> | so that I can <achieve some goal> |
|---|---|---|
| End User | View my VPC's | View an overview of my VPC's |
| API User | Retrieve my VPC's | Perform actions on my vpc data |

### Acceptance Criteria

| GIVEN | WHEN | THEN |
|---|---|---|
| I am on the VPC Summary page | The page loads | I can see all VPCs I have created showing VPC ID, Name, status |
| I request the vpc collection/item | the request completes | I can see the vpc id, name, status, etc |



### Process Flows
 <!-- attach any flow charts and delete placeholder -->
- no process flow required

<!-- ENFORCEMENT-END -->

### Story Tasks
- [ ] Process flow created & Requirements defined
- [ ] Development
- [ ] CI tests
- [ ] User Documentation
- [ ] Code Review


<!--- Set Team label - Delete as appropriate -->
/label ~PHP ~DevOps 

<!--- set product or project labels - If appropriate  -->
/label ~eCloud 

<!--- set product or project milestone - If appropriate  -->
/milestone %

<!--- set initial issue status, priority, weight & estimate - see handbook if unsure  -->
/label ~"To Do" ~P2
/weight 2
/estimate 4h
