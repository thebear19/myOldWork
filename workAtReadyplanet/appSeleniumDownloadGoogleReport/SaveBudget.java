package com.rp.adpro_sor;

import static org.junit.Assert.fail;

import java.io.File;
import java.io.FileInputStream;
import java.io.InputStreamReader;
import java.sql.Connection;
import java.sql.DriverManager;
import java.text.DateFormat;
import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Date;
import java.util.List;
import java.util.Properties;
import java.util.concurrent.TimeUnit;
import java.util.prefs.Preferences;

import javax.mail.Message;
import javax.mail.Session;
import javax.mail.Transport;
import javax.mail.internet.InternetAddress;
import javax.mail.internet.MimeMessage;

import org.ini4j.Ini;
import org.ini4j.IniPreferences;
import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.firefox.FirefoxDriver;
import org.openqa.selenium.firefox.FirefoxProfile;

import com.mysql.jdbc.PreparedStatement;
import com.opencsv.CSVReader;

public class SaveBudget {

	private WebDriver driver;
	private String baseUrl;
	private StringBuffer verificationErrors = new StringBuffer();

	private String ivEMail, ivPassWd, ivLabel, ivRootFolder, ivtargetFolder;

	private List<Integer> countData = new ArrayList<Integer>();

	private Connection con;
	private PreparedStatement pstmt;

	private String dbHost, dbUser, dbPass;

	private CSVReader reader;

	public static void main(String[] args) {

		Ini iniFile = null;
		SaveBudget budget = null;
		try {

			iniFile = new Ini(new File("config.ini"));

			Preferences prefs = new IniPreferences(iniFile);

			budget = new SaveBudget();

			Preferences mainConfig = prefs.node("main");
			String[] sLabels = mainConfig.get("label", "").split(",");

			budget.setTargetFolder(mainConfig.get("targetFolderServer", ""));

			budget.setDbHost(mainConfig.get("db", ""));
			budget.setDbUser(mainConfig.get("dbUsername", ""));
			budget.setDbPass(mainConfig.get("dbPassword", ""));

			budget.setEMail(mainConfig.get("email", ""));
			budget.setPassWd(mainConfig.get("password", ""));
			budget.setRootFolder(mainConfig.get("downloadFolderServer", ""));

			for (String sLabel : sLabels) {

				budget.setLabel(sLabel);

				budget.setUp();
				budget.executeSaveBudget();

				budget.readBudgetFile(budget.getRootFolder()
						+ "myclientcenter.csv");

				budget.copyFileToDestination("myclientcenter" + sLabel + ".csv");

			}

			budget.sendMailReport(sLabels, budget.getCountData());

			budget.tearDown();

		} catch (Exception e) {
			e.printStackTrace();
		} finally {
			System.exit(0);
		}
	}

	private FirefoxDriver getFireFoxDriver() {
		FirefoxProfile fProfile = new FirefoxProfile();
		if (this.getRootFolder() != null && this.getRootFolder().length() > 0) {
			fProfile.setPreference("browser.download.folderList", 2);
			fProfile.setPreference("browser.download.dir", this.getRootFolder());
		}
		fProfile.setPreference("browser.helperApps.neverAsk.saveToDisk",
				"application/csv");

		FirefoxDriver fDriver = new FirefoxDriver(fProfile);

		return fDriver;
	}

	public void setUp() throws Exception {
		driver = getFireFoxDriver();

		baseUrl = "https://accounts.google.com/";
		driver.manage().timeouts().implicitlyWait(30, TimeUnit.SECONDS);
	}

	private void waitForText(By anElement, String sExpectText) throws Exception {
		for (int second = 0;; second++) {
			if (second >= 30)
				break; // fail("timeout");

			String sGoogleText = "" + driver.findElement(anElement).getText();
			System.out.println("Waiting for Text:" + anElement.toString()
					+ "\t Result :" + sGoogleText);
			try {
				if (sGoogleText.contains(sExpectText)) {
					System.out.println("found");
					return;
				}
			} catch (Exception e) {
			}
			System.out.println("sleep " + second + " sec");
			waitForSeconds(1);
		}
		System.out.println("!!! not found !!! " + anElement.toString());
		waitForSeconds(3);
	}

	public void executeSaveBudget() throws Exception {

		// login to adwords mcc accounts
		driver.get(baseUrl
				+ "/ServiceLogin?service=adwords&continue=https://adwords.google.com/um/identity?hl%3Dth&hl=en&ltmpl=signin&passive=0&skipvpage=true");
		driver.findElement(By.id("Email")).clear();
		driver.findElement(By.id("Email")).sendKeys(this.getEMail());
		driver.findElement(By.id("Passwd")).clear();
		driver.findElement(By.id("Passwd")).sendKeys(this.getPassWd());
		driver.findElement(By.id("signIn")).click();
		System.out.println("Login complete.");
		System.out.println("Page Title:" + driver.getTitle());

		waitForText(By.cssSelector("span.aw-pagination-show-rows > span"),
				"Show rows:");

		driver.findElement(By.linkText(this.getLabel())).click();
		waitForText(By.cssSelector("span.aw-pagination-go-to-page > span"),
				"Go to page:");

		driver.findElement(
				By.xpath("//div[@id='gwt-mcm']/div/div[3]/div[2]/div/div/div[2]/div[4]/div/table/tbody/tr/td[3]/div/div/div[3]/span"))
				.click();
		
		waitForText(
				By.xpath("//div[@id='gwt-mcm']/div/div[3]/div[2]/div/div/div[2]/div[4]/div/div[2]/div/div/div/div/div[3]/div/div/div/div[2]"),
				"");
		// waitForSeconds(3);
		driver.findElement(
				By.xpath("//div[@id='gwt-mcm']/div/div[3]/div[2]/div/div/div[2]/div[4]/div/div[2]/div/div/div/div/div[3]/div/div/div/div[2]"))
				.click();

		waitForSeconds(10); // wait until download complete

		// logout
		driver.findElement(
				By.cssSelector("div.aw-cues-customerpanel.aw-cues-downarrow"))
				.click();
		
		//driver.findElement(By.cssSelector("div.qNKB.qDKB")).click();
		driver.findElement(By.xpath("//div[text()='Sign out']")).click();
		waitForSeconds(3); // wait until login complete
	}

	public void waitForSeconds(int nSec) {
		try {
			System.out.println("delay :" + nSec + " seconds.");
			Thread.sleep(nSec * 1000);
		} catch (InterruptedException e) {
			e.printStackTrace();
		}
	}

	private void copyFileToDestination(String fileName) throws Exception {

		DateFormat dateFormat = new SimpleDateFormat("yyyy-MM-dd");
		Date date = new Date();
		String folderName = dateFormat.format(date);

		File directory = new File(getTargetFolder() + folderName);

		if (!directory.exists()) {
			System.out.println("creating directory: " + directory.getName());
			boolean result = false;

			directory.mkdir();
			result = true;

			if (result) {
				System.out.println("directory created");
			}
		}

		File sourcefile = new File(getRootFolder() + "myclientcenter.csv");
		if (sourcefile.renameTo(new File(getTargetFolder() + folderName + "/"
				+ folderName + "-" + fileName))) {
			System.out.println("File is moved successful!");
		} else {
			System.out.println("File is failed to move!");
		}

	}

	public void tearDown() throws Exception {
		// System.out.println("tearDown 1");
		driver.quit();
		// System.out.println("tearDown 2");
		String verificationErrorString = verificationErrors.toString();
		if (!"".equals(verificationErrorString)) {
			fail(verificationErrorString);
		}
	}

	private void readBudgetFile(String fileName) throws Exception {

		List<String> listDomain = new ArrayList<String>();
		List<String> listAdwordID = new ArrayList<String>();
		List<Double> listBudget = new ArrayList<Double>();

		// open file
		reader = new CSVReader(new InputStreamReader(new FileInputStream(
				fileName), "UTF-16"), '\t', '\"', 2);
		String[] nextLine;

		int i = 1;

		// get data each row
		while ((nextLine = reader.readNext()) != null) {

			// check Is data complete
			if (nextLine.length < 25) {
				throw new Exception("Data error at row: " + i);
			}

			// get input and remove (ASCII 0)
			String domain = nextLine[0].trim().replaceAll("\0", "");
			String adwordID = nextLine[2].trim().replaceAll("\0", "");
			String budget = nextLine[12].trim().replaceAll("\0", "");

			// clean format domain
			List<String> domainList = Arrays.asList(domain.replace("\"", "")
					.split(","));
			domain = Unicode2ASCII(domainList.get(0));

			// clean format Adword id
			adwordID = adwordID.substring(0, 3) + "-"
					+ adwordID.substring(3, 6) + "-"
					+ adwordID.substring(6, adwordID.length());

			// clean format budget
			budget = budget.replace("THB", "").replace("$", "")
					.replace("\"", "").replace(",", "");
			if (budget.compareTo("--") == 0) {
				budget = "0.00";
			}
			double budgetNum = Double.parseDouble(budget);

			// save to list prepare for insert DB
			listDomain.add(domain);
			listAdwordID.add(adwordID);
			listBudget.add(budgetNum);

			i++;
		}
		saveData(listDomain, listAdwordID, listBudget);

		setCountData(i);

		reader.close();
	}

	private void saveData(List<String> listDomain, List<String> listAdwordID,
			List<Double> listBudget) throws Exception {

		List<String> listValue = new ArrayList<String>();

		Class.forName("com.mysql.jdbc.Driver");

		con = DriverManager
				.getConnection(getDbHost(), getDbUser(), getDbPass());
		// con = DriverManager.getConnection("jdbc:mysql://localhost/readyplanet_com","root","");

		 String sql = "INSERT INTO adword_remaining_budget (AdwordsCusID, RemainingBudget, AccountName, date, remark, datetime) VALUES ";
		//String sql = "INSERT INTO adword_remaining_budget_test (AdwordsCusID, RemainingBudget, AccountName, date, remark, datetime) VALUES ";

		DateFormat dateTimeFormat = new SimpleDateFormat("yyyy/MM/dd HH:mm:ss");
		DateFormat dateFormat = new SimpleDateFormat("yyyy/MM/dd");
		Date dateAdd = new Date();

		System.out.println("Data list-------------------");

		for (int i = 0; i < listAdwordID.size(); i++) {
			String domain = listDomain.get(i);
			String adwordID = listAdwordID.get(i);
			double budget = listBudget.get(i);

			System.out.println("('" + adwordID + "', '" + budget + "', '"
					+ domain + "', '" + dateFormat.format(dateAdd) + "', '', '"
					+ dateTimeFormat.format(dateAdd) + "')");
			listValue.add("('" + adwordID + "', '" + budget + "', '" + domain
					+ "', '" + dateFormat.format(dateAdd) + "', '', '"
					+ dateTimeFormat.format(dateAdd) + "')");
		}

		sql += listValue.toString().replaceAll("\\[|\\]", "") + ";";

		pstmt = (PreparedStatement) con.prepareStatement(sql);
		pstmt.executeUpdate();

		pstmt.close();
		con.close();
	}

	private void sendMailReport(String[] texts, List<Integer> count)
			throws Exception {
		System.out.println("sending mail...");

		Properties props = new Properties();
		Session session = Session.getDefaultInstance(props, null);

		DateFormat dateFormat = new SimpleDateFormat("dd/MM/yyyy");
		Date today = new Date();

		InternetAddress[] receiver = {
				new InternetAddress("chatchawan@readyplanet.com", "Chatchawan"),
				new InternetAddress("areesa@readyplanet.com", "Areesa"),
				new InternetAddress("saran@readyplanet.com", "Saran") };

		String msgBody = "";
		int i = 0, sum = 0;

		for (String text : texts) {
			msgBody += text + " Report: " + count.get(i)
					+ " rows have saved in to database.\n";

			sum += count.get(i);
			i++;
		}
		
		if(sum < 1000){
			msgBody += "\nWarning: Please check these data.";
		}

		Message msg = new MimeMessage(session);
		msg.setFrom(new InternetAddress("info@readyplanet.com",
				"info@readyplanet.com"));
		msg.addRecipients(Message.RecipientType.TO, receiver);
		msg.setSubject("Remaining Budget - Total saved data report ["
				+ dateFormat.format(today) + "]");
		msg.setText(msgBody);
		Transport.send(msg);

		System.out.println("send completed...");
	}

	private String Unicode2ASCII(String unicode) {

		StringBuffer ascii = new StringBuffer(unicode);

		int code;

		for (int i = 0; i < unicode.length(); i++) {

			code = (int) unicode.charAt(i);

			if ((0xE01 <= code) && (code <= 0xE5B))

				ascii.setCharAt(i, (char) (code - 0xD60));

		}

		return ascii.toString();
	}

	public String getEMail() {
		return ivEMail;
	}

	public void setEMail(String ivEMail) {
		this.ivEMail = ivEMail;
	}

	public String getPassWd() {
		return ivPassWd;
	}

	public void setPassWd(String ivPassWd) {
		this.ivPassWd = ivPassWd;
	}

	public String getLabel() {
		return ivLabel;
	}

	public void setLabel(String ivLabel) {
		this.ivLabel = ivLabel;
	}

	public String getRootFolder() {
		return ivRootFolder;
	}

	public void setRootFolder(String ivRootFolder) {
		this.ivRootFolder = ivRootFolder;
	}

	public String getTargetFolder() {
		return ivtargetFolder;
	}

	public void setTargetFolder(String ivtargetFolder) {
		this.ivtargetFolder = ivtargetFolder;
	}

	public String getDbHost() {
		return dbHost;
	}

	public void setDbHost(String dbHost) {
		this.dbHost = dbHost;
	}

	public String getDbUser() {
		return dbUser;
	}

	public void setDbUser(String dbUser) {
		this.dbUser = dbUser;
	}

	public String getDbPass() {
		return dbPass;
	}

	public void setDbPass(String dbPass) {
		this.dbPass = dbPass;
	}

	public List<Integer> getCountData() {
		return countData;
	}

	public void setCountData(int countData) {
		this.countData.add(countData);
	}

}